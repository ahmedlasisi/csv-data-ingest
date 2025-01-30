<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130105113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE broker ADD code VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE broker ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE broker ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6AAF03B77153098 ON broker (code)');
        $this->addSql('ALTER TABLE client ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE client ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE event ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE financials ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE financials ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE insurer ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE insurer ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE policy ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE policy ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE policy ALTER root_policy_ref DROP NOT NULL');
        $this->addSql('ALTER TABLE policy ALTER company_description DROP NOT NULL');
        $this->addSql('ALTER TABLE product ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE product ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE insurer DROP created_at');
        $this->addSql('ALTER TABLE insurer DROP updated_at');
        $this->addSql('ALTER TABLE event DROP created_at');
        $this->addSql('ALTER TABLE event DROP updated_at');
        $this->addSql('ALTER TABLE product DROP created_at');
        $this->addSql('ALTER TABLE product DROP updated_at');
        $this->addSql('ALTER TABLE client DROP created_at');
        $this->addSql('ALTER TABLE client DROP updated_at');
        $this->addSql('ALTER TABLE financials DROP created_at');
        $this->addSql('ALTER TABLE financials DROP updated_at');
        $this->addSql('ALTER TABLE policy DROP created_at');
        $this->addSql('ALTER TABLE policy DROP updated_at');
        $this->addSql('ALTER TABLE policy ALTER root_policy_ref SET NOT NULL');
        $this->addSql('ALTER TABLE policy ALTER company_description SET NOT NULL');
        $this->addSql('DROP INDEX UNIQ_F6AAF03B77153098');
        $this->addSql('ALTER TABLE broker DROP code');
        $this->addSql('ALTER TABLE broker DROP created_at');
        $this->addSql('ALTER TABLE broker DROP updated_at');
    }
}
