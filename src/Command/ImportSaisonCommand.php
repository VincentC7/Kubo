<?php

namespace App\Command;

use App\Entity\Ingredient;
use App\Entity\TypeIngredient;
use App\Repository\IngredientRepository;
use App\Repository\TypeIngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-saison',
    description: 'Importe le calendrier des fruits et légumes de saison (source : Neary). Idempotent.',
)]
class ImportSaisonCommand extends Command
{
    /**
     * Calendrier complet des fruits et légumes de saison.
     * Source : https://neary.fr/blogs/les-fruits-et-legumes/le-calendrier-de-fruits-et-legumes-de-saison
     *
     * Format : 'nom normalisé' => ['type' => slug, 'mois' => [1..12]]
     * Types utilisés : 'fruit', 'legume', 'autre'
     *
     * Règles de classement :
     *   - Tomate et avocat → fruit (botanique)
     *   - Champignon, noix, châtaigne, coing, nèfle → autre
     *
     * @var array<string, array{type: string, mois: list<int>}>
     */
    private const CATALOGUE = [
        // ── Fruits ───────────────────────────────────────────────────────────
        'abricot'       => ['type' => 'fruit', 'mois' => [6, 7]],
        'ananas'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 5, 6, 7, 11, 12]],
        'avocat'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 11, 12]],
        'banane'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
        'cassis'        => ['type' => 'fruit', 'mois' => [7]],
        'cerise'        => ['type' => 'fruit', 'mois' => [5, 6, 7]],
        'citron'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
        'clémentine'    => ['type' => 'fruit', 'mois' => [1, 2, 11, 12]],
        'coing'         => ['type' => 'autre', 'mois' => [9, 10]],
        'figue'         => ['type' => 'fruit', 'mois' => [7, 8, 9, 10]],
        'fraise'        => ['type' => 'fruit', 'mois' => [4, 5, 6, 7]],
        'framboise'     => ['type' => 'fruit', 'mois' => [6, 7, 8]],
        'grenade'       => ['type' => 'fruit', 'mois' => [1, 10, 11, 12]],
        'groseille'     => ['type' => 'fruit', 'mois' => [7]],
        'kaki'          => ['type' => 'fruit', 'mois' => [1, 10, 11, 12]],
        'kiwi'          => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 11, 12]],
        'mandarine'     => ['type' => 'fruit', 'mois' => [3, 4, 11, 12]],
        'mangue'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 6, 7, 10, 11, 12]],
        'melon'         => ['type' => 'fruit', 'mois' => [6, 7, 8, 9]],
        'mûre'          => ['type' => 'fruit', 'mois' => [7, 8]],
        'myrtille'      => ['type' => 'fruit', 'mois' => [7, 8]],
        'nèfle'         => ['type' => 'autre', 'mois' => [5]],
        'noix'          => ['type' => 'autre', 'mois' => [9, 10, 11]],
        'orange'        => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 5, 11, 12]],
        'papaye'        => ['type' => 'fruit', 'mois' => [4]],
        'pastèque'      => ['type' => 'fruit', 'mois' => [6, 7, 8, 9]],
        'pêche'         => ['type' => 'fruit', 'mois' => [6, 7, 8]],
        'poire'         => ['type' => 'fruit', 'mois' => [1, 2, 3, 8, 9, 10, 11, 12]],
        'pomelo'        => ['type' => 'fruit', 'mois' => [2, 3, 4, 5, 11, 12]],
        'pomme'         => ['type' => 'fruit', 'mois' => [1, 2, 3, 4, 5, 8, 9, 10, 11, 12]],
        'prune'         => ['type' => 'fruit', 'mois' => [8, 9, 10]],
        'raisin'        => ['type' => 'fruit', 'mois' => [8, 9, 10]],
        'tomate'        => ['type' => 'fruit', 'mois' => [6, 7, 8, 9, 10]],
        'fruit de la passion' => ['type' => 'fruit', 'mois' => [4]],
        'châtaigne'     => ['type' => 'autre', 'mois' => [10, 11, 12]],

        // ── Légumes ──────────────────────────────────────────────────────────
        'artichaut'          => ['type' => 'legume', 'mois' => [3, 4, 5, 6, 7, 8, 9, 10]],
        'asperge'            => ['type' => 'legume', 'mois' => [4, 5]],
        'aubergine'          => ['type' => 'legume', 'mois' => [6, 7, 8, 9, 10]],
        'betterave'          => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]],
        'blette'             => ['type' => 'legume', 'mois' => [1, 2, 3, 6, 11, 12]],
        'brocoli'            => ['type' => 'legume', 'mois' => [1, 2, 3, 9, 10, 11, 12]],
        'carotte'            => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
        'céleri branche'     => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'céleri rave'        => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'champignon'         => ['type' => 'autre', 'mois' => [1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12]],
        'chou de bruxelles'  => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'chou-fleur'         => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 10, 11, 12]],
        'chou frisé'         => ['type' => 'legume', 'mois' => [10, 11, 12]],
        'chou rave'          => ['type' => 'legume', 'mois' => [8]],
        'concombre'          => ['type' => 'legume', 'mois' => [4, 5, 6, 7, 8, 9]],
        'courge butternut'   => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'courgette'          => ['type' => 'legume', 'mois' => [4, 5, 6, 7, 8, 9, 10]],
        'endives'            => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 11, 12]],
        'épinard'            => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 11, 12]],
        'fenouil'            => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 9, 10, 11, 12]],
        'fève'               => ['type' => 'legume', 'mois' => [5, 6, 7]],
        'haricot coco'       => ['type' => 'legume', 'mois' => [9, 10]],
        'haricot vert'       => ['type' => 'legume', 'mois' => [6, 7, 8, 9, 10]],
        'navet'              => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 11, 12]],
        'panais'             => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'patate douce'       => ['type' => 'legume', 'mois' => [1, 2, 3, 10, 11, 12]],
        'petit pois'         => ['type' => 'legume', 'mois' => [6, 7]],
        'poireau'            => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 11, 12]],
        'poivron'            => ['type' => 'legume', 'mois' => [6, 7, 8, 9, 10]],
        'pomme de terre'     => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
        'potimarron'         => ['type' => 'legume', 'mois' => [1, 2, 9, 10, 11, 12]],
        'radis'              => ['type' => 'legume', 'mois' => [4, 5, 6, 7, 8, 9, 10]],
        'radis noir'         => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
        'rutabaga'           => ['type' => 'legume', 'mois' => [12]],
        'salade'             => ['type' => 'legume', 'mois' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
        'topinambour'        => ['type' => 'legume', 'mois' => [1, 2, 3, 11, 12]],
    ];

    public function __construct(
        private readonly EntityManagerInterface    $em,
        private readonly IngredientRepository      $ingredientRepo,
        private readonly TypeIngredientRepository  $typeRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import calendrier fruits & légumes de saison');

        // ── 1. Charger les types existants, créer les manquants ──────────────
        $types = $this->typeRepo->findAllIndexedBySlug();

        foreach (['fruit', 'legume', 'autre'] as $slug) {
            if (!isset($types[$slug])) {
                $labels = ['fruit' => 'Fruit', 'legume' => 'Légume', 'autre' => 'Autre'];
                $type   = new TypeIngredient($labels[$slug], $slug);
                $this->em->persist($type);
                $types[$slug] = $type;
                $io->note(sprintf('Type "%s" créé.', $slug));
            }
        }

        // Flush les éventuels nouveaux types avant les ingrédients
        $this->em->flush();

        // ── 2. Charger tous les ingrédients existants, indexés par nom normalisé ──
        /** @var array<string, \App\Entity\Ingredient> $existants */
        $existants = [];
        foreach ($this->ingredientRepo->findAll() as $ing) {
            $existants[$this->normalize($ing->getNom())] = $ing;
        }

        // ── 3. Parcourir le catalogue ─────────────────────────────────────────
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (self::CATALOGUE as $nom => $data) {
            $nomNormalise = $this->normalize($nom);
            $type         = $types[$data['type']];
            $mois         = $data['mois'];

            if (isset($existants[$nomNormalise])) {
                $ingredient = $existants[$nomNormalise];

                // Vérifier si une mise à jour est nécessaire
                $sameType = $ingredient->getType()?->getSlug() === $type->getSlug();
                $sameMois = $ingredient->getMoisSaison() === $mois;

                if ($sameType && $sameMois) {
                    $io->writeln(sprintf('  <fg=gray>  skip  %s (déjà à jour)</>', $nom));
                    ++$skipped;
                    continue;
                }

                $ingredient->setType($type);
                $ingredient->setMoisSaison($mois);
                $io->writeln(sprintf('  <fg=yellow>update</> %s', $nom));
                ++$updated;
            } else {
                $ingredient = new Ingredient($nomNormalise);
                $ingredient->setType($type);
                $ingredient->setMoisSaison($mois);
                $this->em->persist($ingredient);
                $io->writeln(sprintf('  <fg=green>create</> %s', $nom));
                ++$created;
            }
        }

        $this->em->flush();

        // ── 4. Résumé ─────────────────────────────────────────────────────────
        $io->success(sprintf(
            '%d créé(s), %d mis à jour, %d déjà à jour.',
            $created,
            $updated,
            $skipped,
        ));

        return Command::SUCCESS;
    }

    private function normalize(string $nom): string
    {
        return mb_strtolower(trim($nom));
    }
}
