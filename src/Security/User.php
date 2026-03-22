<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Représentation minimale d'un utilisateur authentifié.
 *
 * Pour l'instant, l'identité est un UUID fixe codé en dur (fake authenticator).
 * Quand le JWT sera en place, ce User sera instancié depuis le payload du token
 * et UserIdHeaderAuthenticator sera remplacé par un JWTAuthenticator.
 */
final class User implements UserInterface
{
    public function __construct(private readonly string $id) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void {}
}
