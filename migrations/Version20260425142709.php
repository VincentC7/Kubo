<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260425142709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes first_name et last_name sur la table users';
    }

    public function up(Schema $schema): void
    {
        // Ajout avec valeur par défaut temporaire pour les lignes existantes
        $this->addSql("ALTER TABLE users ADD first_name VARCHAR(100) NOT NULL DEFAULT ''");
        $this->addSql("ALTER TABLE users ADD last_name VARCHAR(100) NOT NULL DEFAULT ''");
        // Suppression de la valeur par défaut (colonne gérée applicativement)
        $this->addSql('ALTER TABLE users ALTER first_name DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER last_name DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP first_name');
        $this->addSql('ALTER TABLE users DROP last_name');
    }
}
