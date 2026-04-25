<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower(trim($input->getArgument('email')));
        $plainPassword = $input->getArgument('password');

        // Validation email
        $emailViolations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Email(),
        ]);

        if (count($emailViolations) > 0) {
            $io->error('Email invalide : ' . $emailViolations[0]->getMessage());

            return Command::FAILURE;
        }

        // Validation password
        $passwordViolations = $this->validator->validate($plainPassword, [
            new Assert\NotBlank(),
            new Assert\Length(min: 8),
        ]);

        if (count($passwordViolations) > 0) {
            $io->error('Mot de passe invalide : ' . $passwordViolations[0]->getMessage());

            return Command::FAILURE;
        }

        // Vérification unicité
        if ($this->userRepository->findOneBy(['email' => $email]) !== null) {
            $io->error(sprintf('Un compte existe déjà avec l\'email "%s".', $email));

            return Command::FAILURE;
        }

        // Création
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $plainPassword),
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur "%s" créé avec succès.', $email));

        return Command::SUCCESS;
    }
}
