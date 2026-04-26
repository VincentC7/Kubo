<?php

namespace App\Dto;

use App\Entity\ShoppingItem;

final readonly class ShoppingItemDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $ingredientName,
        public ?float $quantity,
        public ?string $unit,
        public ?string $category,
        public bool $checked,
        public string $source,
    ) {}

    public static function fromEntity(ShoppingItem $item): self
    {
        return new self(
            id: (string) $item->getId(),
            ingredientName: $item->getIngredientName(),
            quantity: $item->getQuantity(),
            unit: $item->getUnit(),
            category: $item->getCategory(),
            checked: $item->isChecked(),
            source: $item->getSource(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'             => $this->id,
            'ingredientName' => $this->ingredientName,
            'quantity'       => $this->quantity,
            'unit'           => $this->unit,
            'category'       => $this->category,
            'checked'        => $this->checked,
            'source'         => $this->source,
        ];
    }
}
