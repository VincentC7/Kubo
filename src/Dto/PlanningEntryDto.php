<?php

namespace App\Dto;

use App\Entity\PlanningEntry;

final readonly class PlanningEntryDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public array $recette,
        public string $week,
        public int $portions,
        public bool $done,
    ) {}

    public static function fromEntity(PlanningEntry $entry): self
    {
        $recette = $entry->getRecette();

        return new self(
            id: (string) $entry->getId(),
            recette: [
                'id'         => (string) $recette->getId(),
                'nom'        => $recette->getNom(),
                'imageUrl'   => $recette->getImageUrl(),
                'tempsTotal' => $recette->getTempsTotal(),
                'difficulte' => $recette->getDifficulte(),
            ],
            week: $entry->getWeek(),
            portions: $entry->getPortions(),
            done: $entry->isDone(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'recette'  => $this->recette,
            'week'     => $this->week,
            'portions' => $this->portions,
            'done'     => $this->done,
        ];
    }
}
