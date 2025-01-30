<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130151135 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_f6aaf03b77153098');
        $this->addSql('ALTER TABLE broker DROP code');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6AAF03B5E237E06 ON broker (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_F6AAF03B5E237E06');
        $this->addSql('ALTER TABLE broker ADD code VARCHAR(50) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_f6aaf03b77153098 ON broker (code)');
    }
}
