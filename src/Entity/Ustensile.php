<?php

namespace App\Entity;

use App\Repository\UstensileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UstensileRepository::class)]
#[ORM\Table(name: 'ustensiles')]
class Ustensile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    private string $nom;

    #[ORM\ManyToMany(targetEntity: Recette::class, mappedBy: 'ustensiles')]
    private Collection $recettes;

    public function __construct(string $nom)
    {
        $this->nom = $nom;
        $this->recettes = new ArrayCollection();
    }

    public function getId(): ?int
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

    /** @return Collection<int, Recette> */
    public function getRecettes(): Collection
    {
        return $this->recettes;
    }
}
