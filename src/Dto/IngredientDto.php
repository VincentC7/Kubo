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
        public ?string $type,
        /** @var list<int>|null */
        public ?array $mois_saison,
    ) {}

    public static function fromEntity(RecetteIngredient $ri): self
    {
        $ingredient = $ri->getIngredient();

        return new self(
            nom:         $ingredient->getNom(),
            quantite:    $ri->getQuantite(),
            unite:       $ri->getUnite(),
            raw:         $ri->getRaw(),
            type:        $ingredient->getType()?->getSlug(),
            mois_saison: $ingredient->getMoisSaison(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'nom'         => $this->nom,
            'quantite'    => $this->quantite,
            'unite'       => $this->unite,
            'raw'         => $this->raw,
            'type'        => $this->type,
            'mois_saison' => $this->mois_saison,
        ];
    }
}
