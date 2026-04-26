<?php

namespace App\Entity;

use App\Repository\PlanningEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlanningEntryRepository::class)]
#[ORM\Table(name: 'planning_entries', schema: 'user_data')]
#[ORM\UniqueConstraint(columns: ['user_id', 'recette_id', 'week'])]
#[ORM\Index(columns: ['user_id', 'week'])]
class PlanningEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Recette::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Recette $recette;

    #[ORM\Column(length: 8)]
    private string $week;

    #[ORM\Column(type: 'smallint')]
    private int $portions = 2;

    #[ORM\Column]
    private bool $done = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Recette $recette, string $week)
    {
        $this->user      = $user;
        $this->recette   = $recette;
        $this->week      = $week;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getRecette(): Recette { return $this->recette; }
    public function getWeek(): string { return $this->week; }

    public function getPortions(): int { return $this->portions; }
    public function setPortions(int $portions): static { $this->portions = $portions; return $this; }

    public function isDone(): bool { return $this->done; }
    public function setDone(bool $done): static { $this->done = $done; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
