<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224181354 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove direct Card → Project relation (project is accessible via card.column.board.project)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card DROP FOREIGN KEY `FK_161498D3166D1F9C`');
        $this->addSql('DROP INDEX IDX_161498D3166D1F9C ON card');
        $this->addSql('ALTER TABLE card DROP project_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE card ADD project_id INT NOT NULL');
        $this->addSql('ALTER TABLE card ADD CONSTRAINT `FK_161498D3166D1F9C` FOREIGN KEY (project_id) REFERENCES project (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_161498D3166D1F9C ON card (project_id)');
    }
}
