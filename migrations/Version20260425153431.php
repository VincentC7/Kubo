<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Change le type de la colonne ingredients.mois_saison de json à jsonb.
 *
 * Raison : la requête DQL JSONB_CONTAINS utilise l'opérateur PostgreSQL @>
 * qui nécessite le type jsonb. Avec le type json, PostgreSQL effectue un cast
 * implicite à chaque requête, ce qui est inefficace et ne peut pas bénéficier
 * d'un index GIN.
 */
final class Version20260425153431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change ingredients.mois_saison de json à jsonb + index GIN pour les requêtes de saisonnalité';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ingredients ALTER COLUMN mois_saison TYPE jsonb USING mois_saison::jsonb');
        // Index GIN pour accélérer les requêtes JSONB_CONTAINS (filtre saison)
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ingredients_mois_saison ON ingredients USING gin (mois_saison)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_ingredients_mois_saison');
        $this->addSql('ALTER TABLE ingredients ALTER COLUMN mois_saison TYPE json USING mois_saison::json');
    }
}

