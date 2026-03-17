<?php

namespace App\Dto;

use App\Entity\RecetteIngredient;

final readonly class IngredientDto implements \JsonSerializable
{
    public function __construct(
        public string $nom,
        public ?string $quantite,
        public ?string $unite,
        public string $raw,
    ) {}

    public static function fromEntity(RecetteIngredient $ri): self
    {
        return new self(
            nom: $ri->getIngredient()->getNom(),
            quantite: $ri->getQuantite(),
            unite: $ri->getUnite(),
            raw: $ri->getRaw(),
        );
    }

    public function jsonSerialize(): array
    {
        return (array) $this;
    }
}
