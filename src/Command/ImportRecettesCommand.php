<?php

namespace App\Command;

use App\Entity\Etape;
use App\Entity\NutritionFait;
use App\Entity\Recette;
use App\Entity\RecetteIngredient;
use App\Repository\AllergeneRepository;
use App\Repository\IngredientRepository;
use App\Repository\RecetteRepository;
use App\Repository\TagRepository;
use App\Repository\UstensileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-recettes',
    description: 'Importe les recettes depuis les fichiers JSON du répertoire data/',
)]
class ImportRecettesCommand extends Command
{
    // Codes de validation
    public const VIOLATION_NO_ETAPES = 'NO_ETAPES';
    public const VIOLATION_NO_INSTRUCTIONS = 'NO_INSTRUCTIONS';
    public const VIOLATION_NO_INGREDIENTS = 'NO_INGREDIENTS';
    public const VIOLATION_NO_DESCRIPTION = 'NO_DESCRIPTION';

    /** Codes qui entraînent un rejet (la recette n'est pas importée) */
    private const BLOCKING_VIOLATIONS = [
        self::VIOLATION_NO_ETAPES,
        self::VIOLATION_NO_INSTRUCTIONS,
        self::VIOLATION_NO_INGREDIENTS,
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private readonly ManagerRegistry $registry,
        private readonly RecetteRepository $recetteRepository,
        private readonly TagRepository $tagRepository,
        private readonly AllergeneRepository $allergeneRepository,
        private readonly IngredientRepository $ingredientRepository,
        private readonly UstensileRepository $ustensileRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire(service: 'monolog.logger.import')]
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('data-dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire contenant les fichiers JSON', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Nombre de recettes par flush', '50')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limite le nombre de fichiers à importer (pour tests)')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Ignore les recettes déjà présentes en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dataDir = $input->getOption('data-dir') ?? $this->projectDir . '/data';
        $batchSize = (int) $input->getOption('batch-size');
        $limit = $input->getOption('limit') !== null ? (int) $input->getOption('limit') : null;
        $skipExisting = (bool) $input->getOption('skip-existing');

        if (!is_dir($dataDir)) {
            $io->error(sprintf('Le répertoire "%s" n\'existe pas.', $dataDir));
            return Command::FAILURE;
        }

        $files = glob($dataDir . '/*.json');
        if ($files === false || count($files) === 0) {
            $io->error('Aucun fichier JSON trouvé dans ' . $dataDir);
            return Command::FAILURE;
        }

        if ($limit !== null) {
            $files = array_slice($files, 0, $limit);
        }

        $total = count($files);
        $io->title(sprintf('Import de %d fichiers JSON', $total));

        $progressBar = new ProgressBar($output, $total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progressBar->start();

        $imported = 0;
        $skipped = 0;
        $rejected = 0;
        $errors = [];
        $batchCount = 0;

        foreach ($files as $filePath) {
            $fileId = basename($filePath, '.json');
            $progressBar->setMessage($fileId);

            try {
                $json = file_get_contents($filePath);
                if ($json === false) {
                    throw new \RuntimeException('Impossible de lire le fichier.');
                }

                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

                // Dédoublonnage par nom + source
                $nom = trim((string) ($data['nom'] ?? $fileId));
                $source = $data['url'] ?? null;

                if ($skipExisting && $this->recetteRepository->findOneBy(['nom' => $nom]) !== null) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                // Validation
                $violations = $this->validate($fileId, $data);
                $blockingViolations = array_values(array_intersect($violations, self::BLOCKING_VIOLATIONS));

                if (count($blockingViolations) > 0) {
                    $this->logger->warning('Recette rejetée', [
                        'fichier'  => $fileId,
                        'raisons'  => $blockingViolations,
                    ]);
                    $rejected++;
                    $progressBar->advance();
                    continue;
                }

                if (in_array(self::VIOLATION_NO_DESCRIPTION, $violations, true)) {
                    $this->logger->info('Recette importée sans description', ['fichier' => $fileId]);
                }

                $this->importRecette($data);
                $imported++;
                $batchCount++;

                if ($batchCount >= $batchSize) {
                    $this->em->flush();
                    $this->em->clear();
                    $batchCount = 0;
                    // Vider les caches des repositories après clear()
                    $this->tagRepository->clear();
                    $this->allergeneRepository->clear();
                    $this->ingredientRepository->clear();
                    $this->ustensileRepository->clear();
                }
            } catch (\Throwable $e) {
                $this->logger->error('Erreur import', [
                    'fichier' => $fileId,
                    'error'   => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                $errors[] = sprintf('%s : %s', $fileId, $e->getMessage());

                // Si l'EntityManager est fermé suite à une erreur, le rouvrir
                if (!$this->em->isOpen()) {
                    $this->em = $this->registry->resetManager();
                    $batchCount = 0;
                    $this->tagRepository->clear();
                    $this->allergeneRepository->clear();
                    $this->ingredientRepository->clear();
                    $this->ustensileRepository->clear();
                }
            }

            $progressBar->advance();
        }

        // Flush final
        $this->em->flush();

        $progressBar->finish();
        $io->newLine(2);

        $this->logger->info('Import terminé', [
            'importées' => $imported,
            'rejetées'  => $rejected,
            'ignorées'  => $skipped,
            'erreurs'   => count($errors),
        ]);

        $io->success(sprintf('%d recettes importées, %d rejetées, %d ignorées.', $imported, $rejected, $skipped));

        if (count($errors) > 0) {
            $io->warning(sprintf('%d erreur(s) :', count($errors)));
            foreach ($errors as $err) {
                $io->text('  - ' . $err);
            }
        }

        return count($errors) > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Valide les données d'une recette et retourne les codes de violation trouvés.
     *
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private function validate(string $fileId, array $data): array
    {
        $violations = [];

        $etapes = $data['etapes'] ?? [];

        if (count($etapes) === 0) {
            $violations[] = self::VIOLATION_NO_ETAPES;
        } else {
            $hasInstructions = false;
            foreach ($etapes as $etape) {
                if (!empty($etape['instructions'])) {
                    $hasInstructions = true;
                    break;
                }
            }
            if (!$hasInstructions) {
                $violations[] = self::VIOLATION_NO_INSTRUCTIONS;
            }
        }

        if (empty($data['ingredients'])) {
            $violations[] = self::VIOLATION_NO_INGREDIENTS;
        }

        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '' || $description === '-') {
            $violations[] = self::VIOLATION_NO_DESCRIPTION;
        }

        return $violations;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function importRecette(array $data): void
    {
        $nom = trim((string) ($data['nom'] ?? 'Sans nom'));
        $source = isset($data['url']) && $data['url'] !== '' ? $data['url'] : null;

        $recette = new Recette($nom);
        $recette->setSource($source);
        $this->em->persist($recette);

        $description = $data['description'] ?? null;
        $recette->setDescription($description === '-' ? null : $description);
        $recette->setDifficulte($data['difficulte'] ?: null);
        $recette->setTempsTotal($this->parseMinutes($data['temps_total'] ?? ''));
        $recette->setTempsPreparation($this->parseMinutes($data['temps_preparation'] ?? ''));
        $recette->setNbPersonnes((int) ($data['nb_personnes'] ?? 1));
        $recette->setImageUrl($data['image'] ?: null);

        // Tags
        foreach ($data['tags'] ?? [] as $tagNom) {
            $tagNom = $this->normalizeVocabulaire($tagNom);
            if ($tagNom === '') {
                continue;
            }
            $recette->addTag($this->tagRepository->findOrCreate($tagNom));
        }

        // Allergènes
        foreach ($data['allergenes'] ?? [] as $allergeneNom) {
            $allergeneNom = $this->normalizeAllergene($allergeneNom);
            if ($allergeneNom === '') {
                continue;
            }
            $recette->addAllergene($this->allergeneRepository->findOrCreate($allergeneNom));
        }

        // Ustensiles
        foreach ($data['ustensiles'] ?? [] as $ustensileNom) {
            $ustensileNom = trim($ustensileNom);
            if ($ustensileNom === '') {
                continue;
            }
            $recette->addUstensile($this->ustensileRepository->findOrCreate($ustensileNom));
        }

        // Ingrédients
        foreach ($data['ingredients'] ?? [] as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            [$quantite, $unite, $nom] = $this->parseIngredient($raw);
            $ingredient = $this->ingredientRepository->findOrCreate($nom);
            $ri = new RecetteIngredient($recette, $ingredient, $raw);
            $ri->setQuantite($quantite);
            $ri->setUnite($unite);
            $this->em->persist($ri);
            $recette->addRecetteIngredient($ri);
        }

        // Étapes
        foreach ($data['etapes'] ?? [] as $etapeData) {
            $etape = new Etape($recette, (int) ($etapeData['step'] ?? 0));
            $etape->setInstructions(array_values(array_filter(
                (array) ($etapeData['instructions'] ?? []),
                static fn (mixed $v): bool => is_string($v) && trim($v) !== '',
            )));
            $etape->setAstuce($etapeData['astuce'] ?: null);
            $this->em->persist($etape);
            $recette->addEtape($etape);
        }

        // Nutrition
        $nutrition = $data['nutrition'] ?? [];
        foreach ([NutritionFait::CONTEXTE_PORTION => 'portion', NutritionFait::CONTEXTE_100G => '100g'] as $const => $key) {
            $values = $nutrition[$key] ?? [];
            if (empty($values)) {
                continue;
            }
            $nf = new NutritionFait($recette, $const);
            $nf->setEnergieKj($this->parseNutrition($values['Énergie (kJ)'] ?? null));
            $nf->setEnergieKcal($this->parseNutrition($values['Énergie (kcal)'] ?? null));
            $nf->setMatieresGrasses($this->parseNutrition($values['Matières grasses'] ?? null));
            $nf->setAcidesGrasSatures($this->parseNutrition($values['dont acides gras saturés'] ?? null));
            $nf->setGlucides($this->parseNutrition($values['Glucides'] ?? null));
            $nf->setSucres($this->parseNutrition($values['dont sucres'] ?? null));
            $nf->setFibres($this->parseNutrition($values['Fibres alimentaires'] ?? null));
            $nf->setProteines($this->parseNutrition($values['Protéines'] ?? null));
            $nf->setSel($this->parseNutrition($values['Sel'] ?? null));
            $nf->setPotassium($this->parseNutrition($values['Potassium'] ?? null));
            $nf->setCalcium($this->parseNutrition($values['Calcium'] ?? null));
            $nf->setCholesterol($this->parseNutrition($values['Cholestérol'] ?? null));
            $nf->setFer($this->parseNutrition($values['Fer'] ?? $values['Iron'] ?? null));
            $nf->setAcidesGrasTrans($this->parseNutrition($values['Acides gras trans'] ?? $values['Trans Fat'] ?? null));
            $this->em->persist($nf);
            $recette->addNutritionFait($nf);
        }
    }

    /**
     * Convertit "25 minutes", "1 heure 10 minutes", "1h30" en entier (minutes).
     * Retourne null si vide ou non parseable.
     */
    private function parseMinutes(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $total = 0;

        // "1 heure 10 minutes" / "2 heures" / "45 minutes"
        if (preg_match('/(\d+)\s*heure/i', $value, $m)) {
            $total += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*minute/i', $value, $m)) {
            $total += (int) $m[1];
        }

        // Fallback "1h30" ou "1h"
        if ($total === 0 && preg_match('/(\d+)h(\d+)?/i', $value, $m)) {
            $total += (int) $m[1] * 60 + (int) ($m[2] ?? 0);
        }

        return $total > 0 ? $total : null;
    }

    /**
     * Parse une valeur nutritionnelle comme "3343 kJ" ou "45 g" en float.
     */
    private function parseNutrition(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (string) $value;
        if (preg_match('/[\d]+[.,]?[\d]*/', $value, $m)) {
            return (float) str_replace(',', '.', $m[0]);
        }

        return null;
    }

    /**
     * Parse "200 g de poivron rouge" → [quantite, unite, nom].
     *
     * @return array{0: string|null, 1: string|null, 2: string}
     */
    private function parseIngredient(string $raw): array
    {
        // Fractions unicode : ½ → 1/2, ¼ → 1/4, ¾ → 3/4, ⅓ → 1/3, ⅔ → 2/3
        $normalized = strtr($raw, [
            '½' => '1/2', '¼' => '1/4', '¾' => '3/4',
            '⅓' => '1/3', '⅔' => '2/3', '⅛' => '1/8',
        ]);

        $unites = [
            'cuil\. à soupe', 'cuil\. à café', 'cs', 'cc',
            'pièce\(s\)', 'pièce', 'pièces',
            'sachet\(s\)', 'sachet', 'sachets',
            'tranche\(s\)', 'tranche', 'tranches',
            'feuille\(s\)', 'feuille', 'feuilles',
            'gousse\(s\)', 'gousse', 'gousses',
            'botte\(s\)', 'botte', 'bottes',
            'bouquet', 'boîte',
            'selon le goût',
            'ml', 'cl', 'dl', 'l',
            'mg', 'kg', 'g',
        ];
        $unitesPattern = implode('|', $unites);

        $pattern = '/^'
            . '(?P<quantite>\d+(?:[\/\-\.]\d+)?(?:\s*\d+\/\d+)?)?'
            . '\s*'
            . '(?P<unite>' . $unitesPattern . ')?'
            . '\s*(?:de |d\')?'
            . '(?P<nom>.+)$/iu';

        if (preg_match($pattern, trim($normalized), $matches)) {
            $quantite = trim($matches['quantite'] ?? '') ?: null;
            $unite = isset($matches['unite']) ? trim($matches['unite']) : null;
            $nom = trim($matches['nom'] ?? $raw);

            if ($nom === '') {
                $nom = $raw;
            }

            return [$quantite, $unite ?: null, $nom];
        }

        return [null, null, $raw];
    }

    /**
     * Normalise les tags : supprime le préfixe "•" et les espaces superflus.
     */
    private function normalizeVocabulaire(string $value): string
    {
        return trim(ltrim(trim($value), '•'));
    }

    /**
     * Déduplique les allergènes connus (ex: "Gluten/Gluten" → "Gluten").
     */
    private function normalizeAllergene(string $value): string
    {
        $value = $this->normalizeVocabulaire($value);

        if (preg_match('/^(.+?)\/\1$/', $value, $m)) {
            return $m[1];
        }

        return $value;
    }
}
