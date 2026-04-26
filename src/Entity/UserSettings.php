<?php

namespace App\Entity;

use App\Repository\UserSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingsRepository::class)]
#[ORM\Table(name: 'user_settings', schema: 'user_data')]
class UserSettings
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'smallint')]
    private int $portionsDefault = 2;

    #[ORM\Column(type: 'smallint')]
    private int $mealsGoal = 5;

    #[ORM\Column(length: 10)]
    private string $viewMode = 'week';

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $dietaryPrefs = [];

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $notifications = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user)
    {
        $this->user      = $user;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getUser(): User { return $this->user; }

    public function getPortionsDefault(): int { return $this->portionsDefault; }
    public function setPortionsDefault(int $portionsDefault): static { $this->portionsDefault = $portionsDefault; return $this; }

    public function getMealsGoal(): int { return $this->mealsGoal; }
    public function setMealsGoal(int $mealsGoal): static { $this->mealsGoal = $mealsGoal; return $this; }

    public function getViewMode(): string { return $this->viewMode; }
    public function setViewMode(string $viewMode): static { $this->viewMode = $viewMode; return $this; }

    public function getDietaryPrefs(): array { return $this->dietaryPrefs; }
    public function setDietaryPrefs(array $dietaryPrefs): static { $this->dietaryPrefs = $dietaryPrefs; return $this; }

    public function getNotifications(): array { return $this->notifications; }
    public function setNotifications(array $notifications): static { $this->notifications = $notifications; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }
}
