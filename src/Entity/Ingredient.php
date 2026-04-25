<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\Table(name: 'ingredients', schema: 'recette')]
class Ingredient
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $nom;

    #[ORM\ManyToOne(targetEntity: TypeIngredient::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?TypeIngredient $type = null;

    /** @var list<int>|null Mois de saison (1–12), uniquement pour fruits et légumes */
    #[ORM\Column(type: 'json', nullable: true, options: ['jsonb' => true])]
    private ?array $moisSaison = null;

    #[ORM\OneToMany(targetEntity: RecetteIngredient::class, mappedBy: 'ingredient')]
    private Collection $recetteIngredients;

    public function __construct(string $nom)
    {
        $this->nom = $nom;
        $this->recetteIngredients = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?TypeIngredient
    {
        return $this->type;
    }

    public function setType(?TypeIngredient $type): static
    {
        $this->type = $type;

        return $this;
    }

    /** @return list<int>|null */
    public function getMoisSaison(): ?array
    {
        return $this->moisSaison;
    }

    /** @param list<int>|null $moisSaison */
    public function setMoisSaison(?array $moisSaison): static
    {
        $this->moisSaison = $moisSaison;

        return $this;
    }

    /** @return Collection<int, RecetteIngredient> */
    public function getRecetteIngredients(): Collection
    {
        return $this->recetteIngredients;
    }
}
