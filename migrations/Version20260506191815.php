<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260506191815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make iteration.team_id and forecast.team_id non-nullable with ON DELETE CASCADE';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast DROP FOREIGN KEY `FK_2A9C7844296CD8AE`');
        $this->addSql('ALTER TABLE forecast CHANGE team_id team_id INT NOT NULL');
        $this->addSql('ALTER TABLE forecast ADD CONSTRAINT FK_2A9C7844296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE iteration DROP FOREIGN KEY `FK_EED1D11D296CD8AE`');
        $this->addSql('ALTER TABLE iteration CHANGE team_id team_id INT NOT NULL');
        $this->addSql('ALTER TABLE iteration ADD CONSTRAINT FK_EED1D11D296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast DROP FOREIGN KEY FK_2A9C7844296CD8AE');
        $this->addSql('ALTER TABLE forecast CHANGE team_id team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE forecast ADD CONSTRAINT `FK_2A9C7844296CD8AE` FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE iteration DROP FOREIGN KEY FK_EED1D11D296CD8AE');
        $this->addSql('ALTER TABLE iteration CHANGE team_id team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE iteration ADD CONSTRAINT `FK_EED1D11D296CD8AE` FOREIGN KEY (team_id) REFERENCES team (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
