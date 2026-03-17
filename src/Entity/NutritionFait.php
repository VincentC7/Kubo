<?php

namespace App\Entity;

use App\Repository\NutritionFaitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NutritionFaitRepository::class)]
#[ORM\Table(name: 'nutrition_faits')]
#[ORM\UniqueConstraint(name: 'uq_nutrition_recette_contexte', columns: ['recette_id', 'contexte'])]
class NutritionFait
{
    public const CONTEXTE_PORTION = 'portion';
    public const CONTEXTE_100G = '100g';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Recette::class, inversedBy: 'nutritionFaits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recette $recette = null;

    /** 'portion' ou '100g' */
    #[ORM\Column(length: 10, options: ['check' => "contexte IN ('portion', '100g')"])]
    private string $contexte;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $energieKj = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $energieKcal = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $matieresGrasses = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $acidesGrasSatures = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $glucides = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sucres = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fibres = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $proteines = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $sel = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $potassium = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $calcium = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $cholesterol = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $fer = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $acidesGrasTrans = null;

    public function __construct(Recette $recette, string $contexte)
    {
        $this->recette = $recette;
        $this->contexte = $contexte;
    }

    public function getId(): ?Uuid
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

    public function getContexte(): string
    {
        return $this->contexte;
    }

    public function setContexte(string $contexte): static
    {
        $this->contexte = $contexte;

        return $this;
    }

    public function getEnergieKj(): ?float
    {
        return $this->energieKj;
    }

    public function setEnergieKj(?float $energieKj): static
    {
        $this->energieKj = $energieKj;

        return $this;
    }

    public function getEnergieKcal(): ?float
    {
        return $this->energieKcal;
    }

    public function setEnergieKcal(?float $energieKcal): static
    {
        $this->energieKcal = $energieKcal;

        return $this;
    }

    public function getMatieresGrasses(): ?float
    {
        return $this->matieresGrasses;
    }

    public function setMatieresGrasses(?float $matieresGrasses): static
    {
        $this->matieresGrasses = $matieresGrasses;

        return $this;
    }

    public function getAcidesGrasSatures(): ?float
    {
        return $this->acidesGrasSatures;
    }

    public function setAcidesGrasSatures(?float $acidesGrasSatures): static
    {
        $this->acidesGrasSatures = $acidesGrasSatures;

        return $this;
    }

    public function getGlucides(): ?float
    {
        return $this->glucides;
    }

    public function setGlucides(?float $glucides): static
    {
        $this->glucides = $glucides;

        return $this;
    }

    public function getSucres(): ?float
    {
        return $this->sucres;
    }

    public function setSucres(?float $sucres): static
    {
        $this->sucres = $sucres;

        return $this;
    }

    public function getFibres(): ?float
    {
        return $this->fibres;
    }

    public function setFibres(?float $fibres): static
    {
        $this->fibres = $fibres;

        return $this;
    }

    public function getProteines(): ?float
    {
        return $this->proteines;
    }

    public function setProteines(?float $proteines): static
    {
        $this->proteines = $proteines;

        return $this;
    }

    public function getSel(): ?float
    {
        return $this->sel;
    }

    public function setSel(?float $sel): static
    {
        $this->sel = $sel;

        return $this;
    }

    public function getPotassium(): ?float
    {
        return $this->potassium;
    }

    public function setPotassium(?float $potassium): static
    {
        $this->potassium = $potassium;

        return $this;
    }

    public function getCalcium(): ?float
    {
        return $this->calcium;
    }

    public function setCalcium(?float $calcium): static
    {
        $this->calcium = $calcium;

        return $this;
    }

    public function getCholesterol(): ?float
    {
        return $this->cholesterol;
    }

    public function setCholesterol(?float $cholesterol): static
    {
        $this->cholesterol = $cholesterol;

        return $this;
    }

    public function getFer(): ?float
    {
        return $this->fer;
    }

    public function setFer(?float $fer): static
    {
        $this->fer = $fer;

        return $this;
    }

    public function getAcidesGrasTrans(): ?float
    {
        return $this->acidesGrasTrans;
    }

    public function setAcidesGrasTrans(?float $acidesGrasTrans): static
    {
        $this->acidesGrasTrans = $acidesGrasTrans;

        return $this;
    }
}
