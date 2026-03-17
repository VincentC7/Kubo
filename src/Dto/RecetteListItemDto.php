<?php

namespace App\Dto;

use App\Entity\Recette;

final readonly class RecetteListItemDto implements \JsonSerializable
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public string $uuid,
        public string $nom,
        public ?string $description,
        public ?string $image_url,
        public ?int $temps_total,
        public ?string $difficulte,
        public int $nb_personnes,
        public array $tags,
    ) {}

    public static function fromEntity(Recette $recette): self
    {
        $tags = array_values(
            array_map(
                fn ($tag) => $tag->getNom(),
                $recette->getTags()->toArray(),
            )
        );

        return new self(
            uuid: (string) $recette->getId(),
            nom: $recette->getNom(),
            description: $recette->getDescription(),
            image_url: $recette->getImageUrl(),
            temps_total: $recette->getTempsTotal(),
            difficulte: $recette->getDifficulte(),
            nb_personnes: $recette->getNbPersonnes(),
            tags: $tags,
        );
    }

    public function jsonSerialize(): array
    {
        return (array) $this;
    }
}
