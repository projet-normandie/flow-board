<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add closed_at column to card table (finished-card feature).
 */
final class Version20261125203348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add closed_at column to card table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card ADD closed_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE card DROP closed_at');
    }
}
