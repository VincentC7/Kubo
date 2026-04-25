<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDto
{
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    public string $lastName = '';

    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(
        min: 8,
        max: 4096,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'Le mot de passe doit contenir au moins une majuscule.',
    )]
    #[Assert\Regex(
        pattern: '/[0-9]/',
        message: 'Le mot de passe doit contenir au moins un chiffre.',
    )]
    public string $password = '';
}
