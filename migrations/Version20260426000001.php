<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Corrige le default notifications {} → [], ajoute les bornes supérieures CHECK sur portions_default et meals_goal';
    }

    public function up(Schema $schema): void
    {
        // Corrige le default de notifications : {} (objet) → [] (tableau)
        $this->addSql("ALTER TABLE user_data.user_settings ALTER COLUMN notifications SET DEFAULT '[]'");

        // Ajoute la borne supérieure sur portions_default (était CHECK >= 1 seulement)
        $this->addSql('ALTER TABLE user_data.user_settings DROP CONSTRAINT IF EXISTS user_settings_portions_default_check');
        $this->addSql('ALTER TABLE user_data.user_settings ADD CONSTRAINT user_settings_portions_default_check CHECK (portions_default >= 1 AND portions_default <= 20)');

        // Ajoute la borne supérieure sur meals_goal (était CHECK >= 1 seulement)
        $this->addSql('ALTER TABLE user_data.user_settings DROP CONSTRAINT IF EXISTS user_settings_meals_goal_check');
        $this->addSql('ALTER TABLE user_data.user_settings ADD CONSTRAINT user_settings_meals_goal_check CHECK (meals_goal >= 1 AND meals_goal <= 21)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_data.user_settings ALTER COLUMN notifications SET DEFAULT '{}'");

        $this->addSql('ALTER TABLE user_data.user_settings DROP CONSTRAINT IF EXISTS user_settings_portions_default_check');
        $this->addSql('ALTER TABLE user_data.user_settings ADD CONSTRAINT user_settings_portions_default_check CHECK (portions_default >= 1)');

        $this->addSql('ALTER TABLE user_data.user_settings DROP CONSTRAINT IF EXISTS user_settings_meals_goal_check');
        $this->addSql('ALTER TABLE user_data.user_settings ADD CONSTRAINT user_settings_meals_goal_check CHECK (meals_goal >= 1)');
    }
}
