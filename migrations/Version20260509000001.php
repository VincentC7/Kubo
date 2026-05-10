<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260509000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remplace image_url par image_name + image_source_url (interne) sur recette.recettes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recette.recettes ADD COLUMN image_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE recette.recettes ADD COLUMN image_source_url VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE recette.recettes DROP COLUMN image_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recette.recettes ADD COLUMN image_url VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE recette.recettes DROP COLUMN image_name');
        $this->addSql('ALTER TABLE recette.recettes DROP COLUMN image_source_url');
    }
}
