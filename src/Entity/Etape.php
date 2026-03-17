<?php

namespace App\Entity;

use App\Repository\EtapeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtapeRepository::class)]
#[ORM\Table(name: 'etapes')]
class Etape
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'etapes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recette $recette = null;

    #[ORM\Column]
    private int $numero;

    /**
     * Liste de phrases constituant les instructions de l'étape.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $instructions = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $astuce = null;

    public function __construct(Recette $recette, int $numero)
    {
        $this->recette = $recette;
        $this->numero = $numero;
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

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /** @return list<string> */
    public function getInstructions(): array
    {
        return $this->instructions;
    }

    /** @param list<string> $instructions */
    public function setInstructions(array $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    public function getAstuce(): ?string
    {
        return $this->astuce;
    }

    public function setAstuce(?string $astuce): static
    {
        $this->astuce = $astuce;

        return $this;
    }
}
