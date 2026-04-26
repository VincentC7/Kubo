<?php

namespace App\Entity;

use App\Repository\ShoppingItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShoppingItemRepository::class)]
#[ORM\Table(name: 'shopping_items', schema: 'user_data')]
#[ORM\Index(columns: ['shopping_list_id'])]
class ShoppingItem
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: ShoppingList::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ShoppingList $shoppingList;

    #[ORM\Column(length: 255)]
    private string $ingredientName;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $quantity = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column]
    private bool $checked = false;

    #[ORM\Column(length: 20)]
    private string $source = 'manual';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(ShoppingList $shoppingList, string $ingredientName, string $source = 'manual')
    {
        $this->shoppingList   = $shoppingList;
        $this->ingredientName = $ingredientName;
        $this->source         = $source;
        $this->createdAt      = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid { return $this->id; }
    public function getShoppingList(): ShoppingList { return $this->shoppingList; }

    public function getIngredientName(): string { return $this->ingredientName; }
    public function setIngredientName(string $ingredientName): static { $this->ingredientName = $ingredientName; return $this; }

    public function getQuantity(): ?float { return $this->quantity; }
    public function setQuantity(?float $quantity): static { $this->quantity = $quantity; return $this; }

    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(?string $unit): static { $this->unit = $unit; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }

    public function isChecked(): bool { return $this->checked; }
    public function setChecked(bool $checked): static { $this->checked = $checked; return $this; }

    public function getSource(): string { return $this->source; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
