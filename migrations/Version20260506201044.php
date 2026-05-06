<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506201044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add inviter_name column to membership';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership ADD inviter_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership DROP inviter_name');
    }
}
