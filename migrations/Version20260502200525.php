<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502200525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT NOT NULL, ADD email_verification_token VARCHAR(64) DEFAULT NULL, ADD password_reset_token VARCHAR(64) DEFAULT NULL, ADD password_reset_expires_at DATETIME DEFAULT NULL, CHANGE name name VARCHAR(60) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649C4995C67 ON user (email_verification_token)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496B7BA4B6 ON user (password_reset_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team CHANGE name name VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D649C4995C67 ON `user`');
        $this->addSql('DROP INDEX UNIQ_8D93D6496B7BA4B6 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP is_verified, DROP email_verification_token, DROP password_reset_token, DROP password_reset_expires_at, CHANGE name name VARCHAR(255) DEFAULT NULL');
    }
}
