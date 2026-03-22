<?php

namespace App\Dto;

use App\Entity\Ingredient;
use OpenApi\Attributes as OA;

final readonly class SaisonItemDto implements \JsonSerializable
{
    public function __construct(
        #[OA\Property(example: 'tomate')]
        public string $nom,

        #[OA\Property(example: 'legume')]
        public string $type,

        /** @var list<int> */
        #[OA\Property(
            description: 'Mois de saison (1–12)',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [6, 7, 8, 9],
        )]
        public array $mois_saison,
    ) {}

    public static function fromEntity(Ingredient $ingredient): self
    {
        return new self(
            nom:         $ingredient->getNom(),
            type:        $ingredient->getType()->getSlug(),
            mois_saison: $ingredient->getMoisSaison() ?? [],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'nom'         => $this->nom,
            'type'        => $this->type,
            'mois_saison' => $this->mois_saison,
        ];
    }
}
