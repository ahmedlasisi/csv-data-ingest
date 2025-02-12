<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130211813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE financials CHANGE tax_amount ipta_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE policy CHANGE company_description business_description VARCHAR(140) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE financials CHANGE ipta_amount tax_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE policy CHANGE business_description company_description VARCHAR(140) DEFAULT NULL');
    }
}
