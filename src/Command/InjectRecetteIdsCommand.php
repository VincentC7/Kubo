<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:inject-recette-ids',
    description: 'Écrit le recette_id (UUID) dans chaque fichier JSON du répertoire data/',
)]
class InjectRecetteIdsCommand extends Command
{
    public function __construct(
        private readonly RecetteRepository $recetteRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('data-dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire contenant les fichiers JSON', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dataDir = $input->getOption('data-dir') ?? $this->projectDir . '/data';

        $files = glob($dataDir . '/*.json');
        if ($files === false || count($files) === 0) {
            $io->error('Aucun fichier JSON trouvé dans ' . $dataDir);
            return Command::FAILURE;
        }

        // Index toutes les recettes par nom (requête unique)
        $recettes = $this->recetteRepository->findAll();
        $indexByNom = [];
        foreach ($recettes as $recette) {
            $indexByNom[$recette->getNom()] = (string) $recette->getId();
        }

        $matched = 0;
        $notFound = [];

        foreach ($files as $filePath) {
            $json = file_get_contents($filePath);
            if ($json === false) {
                continue;
            }

            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            // Déjà injecté
            if (!empty($data['recette_id'])) {
                $matched++;
                continue;
            }

            $nom = trim((string) ($data['nom'] ?? ''));
            if (!isset($indexByNom[$nom])) {
                $notFound[] = basename($filePath);
                continue;
            }

            $data['recette_id'] = $indexByNom[$nom];

            file_put_contents(
                $filePath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n",
            );

            $matched++;
        }

        $io->success(sprintf('%d fichiers mis à jour.', $matched));

        if (count($notFound) > 0) {
            $io->warning(sprintf('%d fichier(s) sans correspondance en base :', count($notFound)));
            foreach ($notFound as $f) {
                $io->text('  - ' . $f);
            }
        }

        return count($notFound) === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
