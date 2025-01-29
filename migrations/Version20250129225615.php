<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250129225615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE financials (id SERIAL NOT NULL, policy_id INT NOT NULL, insured_amount NUMERIC(20, 2) NOT NULL, premium NUMERIC(10, 2) DEFAULT NULL, commission NUMERIC(10, 2) NOT NULL, admin_fee NUMERIC(10, 2) NOT NULL, tax_amount NUMERIC(10, 2) NOT NULL, policy_fee NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C400A0622D29E3C6 ON financials (policy_id)');
        $this->addSql('ALTER TABLE financials ADD CONSTRAINT FK_C400A0622D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE financials DROP CONSTRAINT FK_C400A0622D29E3C6');
        $this->addSql('DROP TABLE financials');
    }
}
