<?php

namespace App\Dto;

use App\Entity\UserSettings;

final readonly class UserSettingsDto implements \JsonSerializable
{
    public function __construct(
        public int $portionsDefault,
        public int $mealsGoal,
        public string $viewMode,
        public array $dietaryPrefs,
        public array $notifications,
    ) {}

    public static function fromEntity(UserSettings $settings): self
    {
        return new self(
            portionsDefault: $settings->getPortionsDefault(),
            mealsGoal: $settings->getMealsGoal(),
            viewMode: $settings->getViewMode(),
            dietaryPrefs: $settings->getDietaryPrefs(),
            notifications: $settings->getNotifications(),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'portionsDefault' => $this->portionsDefault,
            'mealsGoal'       => $this->mealsGoal,
            'viewMode'        => $this->viewMode,
            'dietaryPrefs'    => $this->dietaryPrefs,
            'notifications'   => $this->notifications,
        ];
    }
}
