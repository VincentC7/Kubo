<?php

namespace App\Entity;

use App\Repository\RecetteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecetteRepository::class)]
#[ORM\Table(name: 'recettes')]
class Recette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 512)]
    private string $url;

    #[ORM\Column(length: 255)]
    private string $nom;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true, options: ['check' => "difficulte IN ('Facile', 'Intermédiaire', 'Difficile')"])]
    private ?string $difficulte = null;

    #[ORM\Column(nullable: true)]
    private ?int $tempsTotal = null;

    #[ORM\Column(nullable: true)]
    private ?int $tempsPreparation = null;

    #[ORM\Column]
    private int $nbPersonnes = 1;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'recettes')]
    #[ORM\JoinTable(name: 'recette_tags')]
    private Collection $tags;

    #[ORM\ManyToMany(targetEntity: Allergene::class, inversedBy: 'recettes')]
    #[ORM\JoinTable(name: 'recette_allergenes')]
    private Collection $allergenes;

    #[ORM\ManyToMany(targetEntity: Ustensile::class, inversedBy: 'recettes')]
    #[ORM\JoinTable(name: 'recette_ustensiles')]
    private Collection $ustensiles;

    #[ORM\OneToMany(targetEntity: RecetteIngredient::class, mappedBy: 'recette', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $recetteIngredients;

    #[ORM\OneToMany(targetEntity: Etape::class, mappedBy: 'recette', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['numero' => 'ASC'])]
    private Collection $etapes;

    #[ORM\OneToMany(targetEntity: NutritionFait::class, mappedBy: 'recette', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $nutritionFaits;

    public function __construct(string $slug, string $url, string $nom)
    {
        $this->slug = $slug;
        $this->url = $url;
        $this->nom = $nom;
        $this->tags = new ArrayCollection();
        $this->allergenes = new ArrayCollection();
        $this->ustensiles = new ArrayCollection();
        $this->recetteIngredients = new ArrayCollection();
        $this->etapes = new ArrayCollection();
        $this->nutritionFaits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDifficulte(): ?string
    {
        return $this->difficulte;
    }

    public function setDifficulte(?string $difficulte): static
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getTempsTotal(): ?int
    {
        return $this->tempsTotal;
    }

    public function setTempsTotal(?int $tempsTotal): static
    {
        $this->tempsTotal = $tempsTotal;

        return $this;
    }

    public function getTempsPreparation(): ?int
    {
        return $this->tempsPreparation;
    }

    public function setTempsPreparation(?int $tempsPreparation): static
    {
        $this->tempsPreparation = $tempsPreparation;

        return $this;
    }

    public function getNbPersonnes(): int
    {
        return $this->nbPersonnes;
    }

    public function setNbPersonnes(int $nbPersonnes): static
    {
        $this->nbPersonnes = $nbPersonnes;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    /** @return Collection<int, Allergene> */
    public function getAllergenes(): Collection
    {
        return $this->allergenes;
    }

    public function addAllergene(Allergene $allergene): static
    {
        if (!$this->allergenes->contains($allergene)) {
            $this->allergenes->add($allergene);
        }

        return $this;
    }

    public function removeAllergene(Allergene $allergene): static
    {
        $this->allergenes->removeElement($allergene);

        return $this;
    }

    /** @return Collection<int, Ustensile> */
    public function getUstensiles(): Collection
    {
        return $this->ustensiles;
    }

    public function addUstensile(Ustensile $ustensile): static
    {
        if (!$this->ustensiles->contains($ustensile)) {
            $this->ustensiles->add($ustensile);
        }

        return $this;
    }

    public function removeUstensile(Ustensile $ustensile): static
    {
        $this->ustensiles->removeElement($ustensile);

        return $this;
    }

    /** @return Collection<int, RecetteIngredient> */
    public function getRecetteIngredients(): Collection
    {
        return $this->recetteIngredients;
    }

    public function addRecetteIngredient(RecetteIngredient $recetteIngredient): static
    {
        if (!$this->recetteIngredients->contains($recetteIngredient)) {
            $this->recetteIngredients->add($recetteIngredient);
            $recetteIngredient->setRecette($this);
        }

        return $this;
    }

    public function removeRecetteIngredient(RecetteIngredient $recetteIngredient): static
    {
        if ($this->recetteIngredients->removeElement($recetteIngredient)) {
            if ($recetteIngredient->getRecette() === $this) {
                $recetteIngredient->setRecette(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Etape> */
    public function getEtapes(): Collection
    {
        return $this->etapes;
    }

    public function addEtape(Etape $etape): static
    {
        if (!$this->etapes->contains($etape)) {
            $this->etapes->add($etape);
            $etape->setRecette($this);
        }

        return $this;
    }

    public function removeEtape(Etape $etape): static
    {
        if ($this->etapes->removeElement($etape)) {
            if ($etape->getRecette() === $this) {
                $etape->setRecette(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, NutritionFait> */
    public function getNutritionFaits(): Collection
    {
        return $this->nutritionFaits;
    }

    public function addNutritionFait(NutritionFait $nutritionFait): static
    {
        if (!$this->nutritionFaits->contains($nutritionFait)) {
            $this->nutritionFaits->add($nutritionFait);
            $nutritionFait->setRecette($this);
        }

        return $this;
    }

    public function removeNutritionFait(NutritionFait $nutritionFait): static
    {
        if ($this->nutritionFaits->removeElement($nutritionFait)) {
            if ($nutritionFait->getRecette() === $this) {
                $nutritionFait->setRecette(null);
            }
        }

        return $this;
    }
}
