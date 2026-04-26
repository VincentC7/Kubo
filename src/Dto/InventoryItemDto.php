<?php

namespace App\Dto;

use App\Entity\InventoryItem;

final readonly class InventoryItemDto implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public float $quantity,
        public ?string $unit,
        public ?string $category,
        public ?string $expiresAt,
        public ?int $daysUntilExpiry,
        public ?string $status,
    ) {}

    public static function fromEntity(InventoryItem $item): self
    {
        $expiresAt       = $item->getExpiresAt();
        $daysUntilExpiry = null;
        $status          = null;

        if ($expiresAt !== null) {
            $today           = new \DateTimeImmutable('today');
            $diff            = (int) $today->diff($expiresAt)->days;
            $isPast          = $expiresAt < $today;
            $daysUntilExpiry = $isPast ? -$diff : $diff;

            if ($isPast) {
                $status = 'expired';
            } elseif ($diff <= 3) {
                $status = 'expiring_soon';
            } else {
                $status = 'ok';
            }
        }

        return new self(
            id: (string) $item->getId(),
            name: $item->getName(),
            quantity: $item->getQuantity(),
            unit: $item->getUnit(),
            category: $item->getCategory(),
            expiresAt: $expiresAt?->format('Y-m-d'),
            daysUntilExpiry: $daysUntilExpiry,
            status: $status,
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'quantity'        => $this->quantity,
            'unit'            => $this->unit,
            'category'        => $this->category,
            'expiresAt'       => $this->expiresAt,
            'daysUntilExpiry' => $this->daysUntilExpiry,
            'status'          => $this->status,
        ];
    }
}
