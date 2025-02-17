<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130171651 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE broker (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F6AAF03B5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, client_ref VARCHAR(50) NOT NULL, client_type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C744045533CBAD07 (client_ref), INDEX IDX_C74404556CC064FC (broker_id), UNIQUE INDEX UNIQ_C74404556CC064FC33CBAD07 (broker_id, client_ref), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event (id INT AUTO_INCREMENT NOT NULL, broker_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_3BAE0AA75E237E06 (name), INDEX IDX_3BAE0AA76CC064FC (broker_id), UNIQUE INDEX UNIQ_3BAE0AA76CC064FC5E237E06 (broker_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE financials (id INT AUTO_INCREMENT NOT NULL, policy_id INT NOT NULL, broker_id INT NOT NULL, insured_amount NUMERIC(20, 2) NOT NULL, premium NUMERIC(10, 2) DEFAULT NULL, commission NUMERIC(10, 2) NOT NULL, admin_fee NUMERIC(10, 2) NOT NULL, tax_amount NUMERIC(10, 2) NOT NULL, policy_fee NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C400A0622D29E3C6 (policy_id), INDEX IDX_C400A0626CC064FC (broker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE insurer (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A2DB411F5E237E06 (name), INDEX IDX_A2DB411F6CC064FC (broker_id), UNIQUE INDEX UNIQ_A2DB411F6CC064FC5E237E06 (broker_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE policy (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, insurer_id INT NOT NULL, product_id INT NOT NULL, event_id INT NOT NULL, broker_id INT DEFAULT NULL, policy_number VARCHAR(50) NOT NULL, insurer_policy_number VARCHAR(50) NOT NULL, root_policy_ref VARCHAR(50) DEFAULT NULL, policy_type VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, effective_date DATE NOT NULL, renewal_date DATE NOT NULL, company_description VARCHAR(140) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_F07D0516BC44F249 (policy_number), UNIQUE INDEX UNIQ_F07D0516C716E0B8 (insurer_policy_number), UNIQUE INDEX UNIQ_F07D0516A8D2BF27 (root_policy_ref), INDEX IDX_F07D051619EB6921 (client_id), INDEX IDX_F07D0516895854C7 (insurer_id), INDEX IDX_F07D05164584665A (product_id), INDEX IDX_F07D051671F7E88B (event_id), INDEX IDX_F07D05166CC064FC (broker_id), UNIQUE INDEX UNIQ_F07D05166CC064FCBC44F249 (broker_id, policy_number), UNIQUE INDEX UNIQ_F07D0516895854C7C716E0B8 (insurer_id, insurer_policy_number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D34A04AD5E237E06 (name), INDEX IDX_D34A04AD6CC064FC (broker_id), UNIQUE INDEX UNIQ_D34A04AD6CC064FC5E237E06 (broker_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404556CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE financials ADD CONSTRAINT FK_C400A0622D29E3C6 FOREIGN KEY (policy_id) REFERENCES policy (id)');
        $this->addSql('ALTER TABLE financials ADD CONSTRAINT FK_C400A0626CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE insurer ADD CONSTRAINT FK_A2DB411F6CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516895854C7 FOREIGN KEY (insurer_id) REFERENCES insurer (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D05164584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051671F7E88B FOREIGN KEY (event_id) REFERENCES event (id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D05166CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD6CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404556CC064FC');
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA76CC064FC');
        $this->addSql('ALTER TABLE financials DROP FOREIGN KEY FK_C400A0622D29E3C6');
        $this->addSql('ALTER TABLE financials DROP FOREIGN KEY FK_C400A0626CC064FC');
        $this->addSql('ALTER TABLE insurer DROP FOREIGN KEY FK_A2DB411F6CC064FC');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D0516895854C7');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D05164584665A');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051671F7E88B');
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D05166CC064FC');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD6CC064FC');
        $this->addSql('DROP TABLE broker');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE financials');
        $this->addSql('DROP TABLE insurer');
        $this->addSql('DROP TABLE policy');
        $this->addSql('DROP TABLE product');
    }
}
