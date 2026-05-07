<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260123100119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial forecast, iteration, and team tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE forecast (id INT AUTO_INCREMENT NOT NULL, target_output INT NOT NULL, number_of_simulations INT NOT NULL, target_iterations INT NOT NULL, result DOUBLE PRECISION DEFAULT NULL, team_id INT DEFAULT NULL, INDEX IDX_2A9C7844296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE iteration (id INT AUTO_INCREMENT NOT NULL, end_date DATE NOT NULL, output INT NOT NULL, team_id INT DEFAULT NULL, INDEX IDX_EED1D11D296CD8AE (team_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE forecast ADD CONSTRAINT FK_2A9C7844296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE iteration ADD CONSTRAINT FK_EED1D11D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast DROP FOREIGN KEY FK_2A9C7844296CD8AE');
        $this->addSql('ALTER TABLE iteration DROP FOREIGN KEY FK_EED1D11D296CD8AE');
        $this->addSql('DROP TABLE forecast');
        $this->addSql('DROP TABLE iteration');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
