<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313194008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE checklist (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, position INT NOT NULL, card_id INT NOT NULL, INDEX IDX_5C696D2F4ACC9A20 (card_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE checklist_item (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, position INT NOT NULL, checked TINYINT NOT NULL, checklist_id INT NOT NULL, INDEX IDX_99EB20F9B16D08A7 (checklist_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE checklist ADD CONSTRAINT FK_5C696D2F4ACC9A20 FOREIGN KEY (card_id) REFERENCES card (id)');
        $this->addSql('ALTER TABLE checklist_item ADD CONSTRAINT FK_99EB20F9B16D08A7 FOREIGN KEY (checklist_id) REFERENCES checklist (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE checklist DROP FOREIGN KEY FK_5C696D2F4ACC9A20');
        $this->addSql('ALTER TABLE checklist_item DROP FOREIGN KEY FK_99EB20F9B16D08A7');
        $this->addSql('DROP TABLE checklist');
        $this->addSql('DROP TABLE checklist_item');
    }
}
