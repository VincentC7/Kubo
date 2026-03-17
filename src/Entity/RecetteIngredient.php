<?php

namespace App\Entity;

use App\Repository\RecetteIngredientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteIngredientRepository::class)]
#[ORM\Table(name: 'recette_ingredients')]
class RecetteIngredient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'recetteIngredients')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recette $recette = null;

    #[ORM\ManyToOne(targetEntity: Ingredient::class, inversedBy: 'recetteIngredients')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ingredient $ingredient = null;

    /** Quantité numérique ou fractionnaire (ex: "200", "1/2", "3-4") */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $quantite = null;

    /** Unité de mesure (ex: "g", "cl", "cuil. à soupe", "pincée") */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unite = null;

    /** Chaîne brute originale avant parsing (ex: "200 g de poivron rouge") */
    #[ORM\Column(length: 512)]
    private string $raw;

    public function __construct(Recette $recette, Ingredient $ingredient, string $raw)
    {
        $this->recette = $recette;
        $this->ingredient = $ingredient;
        $this->raw = $raw;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecette(): ?Recette
    {
        return $this->recette;
    }

    public function setRecette(?Recette $recette): static
    {
        $this->recette = $recette;

        return $this;
    }

    public function getIngredient(): ?Ingredient
    {
        return $this->ingredient;
    }

    public function setIngredient(?Ingredient $ingredient): static
    {
        $this->ingredient = $ingredient;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(?string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): static
    {
        $this->unite = $unite;

        return $this;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function setRaw(string $raw): static
    {
        $this->raw = $raw;

        return $this;
    }
}
