<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:link-images',
    description: 'Lie les images locales aux recettes via recette_id dans les JSON',
)]
class LinkImagesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RecetteRepository $recetteRepository,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('data-dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire contenant les fichiers JSON (défaut : ../kubo-data/recettes/valid)', null)
            ->addOption('image-dir', null, InputOption::VALUE_OPTIONAL, 'Répertoire contenant les images (défaut : data/image)', null)
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Nombre de recettes par flush', '100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dataDir = $input->getOption('data-dir') ?? $this->projectDir . '/kubo-data/recettes/valid';
        $imageDir = $input->getOption('image-dir') ?? $this->projectDir . '/data/image';
        $batchSize = (int) $input->getOption('batch-size');

        $files = glob($dataDir . '/*.json');
        if ($files === false || count($files) === 0) {
            $io->error('Aucun fichier JSON trouvé dans ' . $dataDir);
            return Command::FAILURE;
        }

        $io->title(sprintf('Liaison des images pour %d fichiers JSON', count($files)));

        $progressBar = new ProgressBar($output, count($files));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $progressBar->start();

        $linked = 0;
        $skipped = 0;
        $errors = [];
        $batchCount = 0;

        foreach ($files as $filePath) {
            $baseName = basename($filePath, '.json');
            $progressBar->setMessage($baseName);

            try {
                $data = json_decode((string) file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);

                $recetteId = $data['recette_id'] ?? null;
                if ($recetteId === null) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $recette = $this->recetteRepository->find(Uuid::fromString($recetteId));
                if ($recette === null) {
                    $errors[] = sprintf('%s : recette_id %s introuvable en base', $baseName, $recetteId);
                    $progressBar->advance();
                    continue;
                }

                // Déjà lié
                if ($recette->getImageName() !== null) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $imagePath = $imageDir . '/' . $baseName . '.jpg';
                if (!file_exists($imagePath)) {
                    $errors[] = sprintf('%s : image introuvable', $baseName);
                    $progressBar->advance();
                    continue;
                }

                $recette->setImageName($baseName . '.jpg');
                $recette->setImageSourceUrl($data['image'] ?? null);

                $linked++;
                $batchCount++;

                if ($batchCount >= $batchSize) {
                    $this->em->flush();
                    $this->em->clear();
                    $batchCount = 0;
                }
            } catch (\Throwable $e) {
                $errors[] = sprintf('%s : %s', $baseName, $e->getMessage());
            }

            $progressBar->advance();
        }

        $this->em->flush();
        $progressBar->finish();
        $io->newLine(2);

        $io->success(sprintf('%d images liées, %d ignorées (déjà liées ou sans recette_id).', $linked, $skipped));

        if (count($errors) > 0) {
            $io->warning(sprintf('%d erreur(s) :', count($errors)));
            foreach ($errors as $err) {
                $io->text('  - ' . $err);
            }
        }

        return count($errors) === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
