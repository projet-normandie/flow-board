<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260224120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Board entity: create board table and add board_id FK to column table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE board (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_58562B47166D1F9C (project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE board ADD CONSTRAINT FK_58562B47166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE `column` ADD board_id INT NOT NULL');
        $this->addSql('ALTER TABLE `column` ADD CONSTRAINT FK_7D53381E22BBED8C FOREIGN KEY (board_id) REFERENCES board (id)');
        $this->addSql('CREATE INDEX IDX_7D53381E22BBED8C ON `column` (board_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `column` DROP FOREIGN KEY FK_7D53381E22BBED8C');
        $this->addSql('DROP INDEX IDX_7D53381E22BBED8C ON `column`');
        $this->addSql('ALTER TABLE `column` DROP COLUMN board_id');
        $this->addSql('ALTER TABLE board DROP FOREIGN KEY FK_58562B47166D1F9C');
        $this->addSql('DROP TABLE board');
    }
}
