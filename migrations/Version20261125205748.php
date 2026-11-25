<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125205748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset password request columns to the user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD reset_password_selector VARCHAR(20) DEFAULT NULL, ADD reset_password_hashed_token VARCHAR(100) DEFAULT NULL, ADD reset_password_requested_at DATETIME DEFAULT NULL, ADD reset_password_expires_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP reset_password_selector, DROP reset_password_hashed_token, DROP reset_password_requested_at, DROP reset_password_expires_at');
    }
}
