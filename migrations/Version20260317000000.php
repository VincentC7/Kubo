<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Créer les schémas PostgreSQL recette et auth';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS recette');
        $this->addSql('CREATE SCHEMA IF NOT EXISTS auth');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SCHEMA IF EXISTS auth CASCADE');
        $this->addSql('DROP SCHEMA IF EXISTS recette CASCADE');
    }
}
