<?php

namespace App\Dto;

use App\Entity\ShoppingItem;
use App\Entity\ShoppingList;

final readonly class ShoppingListDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $week,
        public array $items,
    ) {}

    public static function fromEntity(ShoppingList $list): self
    {
        $items = array_map(
            fn (ShoppingItem $item) => ShoppingItemDto::fromEntity($item)->jsonSerialize(),
            $list->getItems()->toArray(),
        );

        return new self(
            id: (string) $list->getId(),
            week: $list->getWeek(),
            items: array_values($items),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'    => $this->id,
            'week'  => $this->week,
            'items' => $this->items,
        ];
    }
}
