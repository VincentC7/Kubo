<?php

namespace App\Dto;

use App\Entity\Recette;

final readonly class RecetteDetailDto implements \JsonSerializable
{
    /**
     * @param list<string>    $tags
     * @param list<string>    $allergenes
     * @param list<string>    $ustensiles
     * @param IngredientDto[] $ingredients
     * @param EtapeDto[]      $etapes
     * @param NutritionDto[]  $nutrition
     */
    public function __construct(
        public string $uuid,
        public string $nom,
        public ?string $description,
        public ?string $image_url,
        public ?string $source,
        public ?int $temps_total,
        public ?int $temps_preparation,
        public ?string $difficulte,
        public int $nb_personnes,
        public array $tags,
        public array $allergenes,
        public array $ustensiles,
        public array $ingredients,
        public array $etapes,
        public array $nutrition,
    ) {}

    public static function fromEntity(Recette $recette): self
    {
        $tags = array_values(
            array_map(fn ($t) => $t->getNom(), $recette->getTags()->toArray())
        );

        $allergenes = array_values(
            array_map(fn ($a) => $a->getNom(), $recette->getAllergenes()->toArray())
        );

        $ustensiles = array_values(
            array_map(fn ($u) => $u->getNom(), $recette->getUstensiles()->toArray())
        );

        $ingredients = array_values(
            array_map(
                fn ($ri) => IngredientDto::fromEntity($ri),
                $recette->getRecetteIngredients()->toArray(),
            )
        );

        $etapes = array_values(
            array_map(
                fn ($e) => EtapeDto::fromEntity($e),
                $recette->getEtapes()->toArray(),
            )
        );

        $nutrition = array_values(
            array_map(
                fn ($nf) => NutritionDto::fromEntity($nf),
                $recette->getNutritionFaits()->toArray(),
            )
        );

        return new self(
            uuid: (string) $recette->getId(),
            nom: $recette->getNom(),
            description: $recette->getDescription(),
            image_url: $recette->getImageUrl(),
            source: $recette->getSource(),
            temps_total: $recette->getTempsTotal(),
            temps_preparation: $recette->getTempsPreparation(),
            difficulte: $recette->getDifficulte(),
            nb_personnes: $recette->getNbPersonnes(),
            tags: $tags,
            allergenes: $allergenes,
            ustensiles: $ustensiles,
            ingredients: $ingredients,
            etapes: $etapes,
            nutrition: $nutrition,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid'              => $this->uuid,
            'nom'               => $this->nom,
            'description'       => $this->description,
            'image_url'         => $this->image_url,
            'source'            => $this->source,
            'temps_total'       => $this->temps_total,
            'temps_preparation' => $this->temps_preparation,
            'difficulte'        => $this->difficulte,
            'nb_personnes'      => $this->nb_personnes,
            'tags'              => $this->tags,
            'allergenes'        => $this->allergenes,
            'ustensiles'        => $this->ustensiles,
            'ingredients'       => $this->ingredients,
            'etapes'            => $this->etapes,
            'nutrition'         => $this->nutrition,
        ];
    }
}
