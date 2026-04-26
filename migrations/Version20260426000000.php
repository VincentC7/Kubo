<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée le schéma user_data et les tables planning_entries, shopping_lists, shopping_items, inventory_items, user_settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS user_data');

        $this->addSql(<<<'SQL'
            CREATE TABLE user_data.planning_entries (
                id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id     UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
                recette_id  UUID NOT NULL REFERENCES recette.recettes(id) ON DELETE CASCADE,
                week        VARCHAR(8) NOT NULL,
                portions    SMALLINT NOT NULL DEFAULT 2 CHECK (portions >= 1 AND portions <= 20),
                done        BOOLEAN NOT NULL DEFAULT FALSE,
                created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                UNIQUE (user_id, recette_id, week)
            )
        SQL);
        $this->addSql('CREATE INDEX ON user_data.planning_entries (user_id, week)');

        $this->addSql(<<<'SQL'
            CREATE TABLE user_data.shopping_lists (
                id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id     UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
                week        VARCHAR(8) NOT NULL,
                created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                updated_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                UNIQUE (user_id, week)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE user_data.shopping_items (
                id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                shopping_list_id UUID NOT NULL REFERENCES user_data.shopping_lists(id) ON DELETE CASCADE,
                ingredient_name  VARCHAR(255) NOT NULL,
                quantity         NUMERIC(10,2),
                unit             VARCHAR(50),
                category         VARCHAR(100),
                checked          BOOLEAN NOT NULL DEFAULT FALSE,
                source           VARCHAR(20) NOT NULL DEFAULT 'manual'
                                   CHECK (source IN ('planning', 'manual')),
                created_at       TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);
        $this->addSql('CREATE INDEX ON user_data.shopping_items (shopping_list_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE user_data.inventory_items (
                id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id     UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
                name        VARCHAR(255) NOT NULL,
                quantity    NUMERIC(10,2) NOT NULL DEFAULT 1,
                unit        VARCHAR(50),
                category    VARCHAR(100),
                expires_at  DATE,
                created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
                updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);
        $this->addSql('CREATE INDEX ON user_data.inventory_items (user_id)');
        $this->addSql('CREATE INDEX ON user_data.inventory_items (user_id, expires_at)');

        $this->addSql(<<<'SQL'
            CREATE TABLE user_data.user_settings (
                user_id          UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
                portions_default SMALLINT NOT NULL DEFAULT 2 CHECK (portions_default >= 1),
                meals_goal       SMALLINT NOT NULL DEFAULT 5 CHECK (meals_goal >= 1),
                view_mode        VARCHAR(10) NOT NULL DEFAULT 'week'
                                   CHECK (view_mode IN ('week', 'list')),
                dietary_prefs    JSONB NOT NULL DEFAULT '[]',
                notifications    JSONB NOT NULL DEFAULT '{}',
                updated_at       TIMESTAMPTZ NOT NULL DEFAULT now()
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_data.user_settings');
        $this->addSql('DROP TABLE IF EXISTS user_data.inventory_items');
        $this->addSql('DROP TABLE IF EXISTS user_data.shopping_items');
        $this->addSql('DROP TABLE IF EXISTS user_data.shopping_lists');
        $this->addSql('DROP TABLE IF EXISTS user_data.planning_entries');
        $this->addSql('DROP SCHEMA IF EXISTS user_data');
    }
}
