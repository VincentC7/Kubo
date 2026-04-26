<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserSettings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const REF_USER       = 'user-standard';
    public const REF_USER_OTHER = 'user-other';
    public const REF_ADMIN      = 'user-admin';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // User standard
        $user = new User();
        $user->setFirstName('Jean');
        $user->setLastName('Dupont');
        $user->setEmail('user@kubo.dev');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Password1'));
        $manager->persist($user);
        $manager->persist(new UserSettings($user));
        $this->addReference(self::REF_USER, $user);

        // Second user (pour tester les 403)
        $other = new User();
        $other->setFirstName('Alice');
        $other->setLastName('Martin');
        $other->setEmail('other@kubo.dev');
        $other->setRoles(['ROLE_USER']);
        $other->setPassword($this->passwordHasher->hashPassword($other, 'Password1'));
        $manager->persist($other);
        $manager->persist(new UserSettings($other));
        $this->addReference(self::REF_USER_OTHER, $other);

        // Admin
        $admin = new User();
        $admin->setFirstName('Admin');
        $admin->setLastName('Kubo');
        $admin->setEmail('admin@kubo.dev');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'AdminPass1'));
        $manager->persist($admin);
        $manager->persist(new UserSettings($admin));
        $this->addReference(self::REF_ADMIN, $admin);

        $manager->flush();
    }
}
