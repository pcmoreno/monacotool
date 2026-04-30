<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260427153655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at to forecast with backfill for existing rows';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE forecast ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE forecast SET created_at = NOW() WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE forecast MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast DROP created_at');
    }
}
