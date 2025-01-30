<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130001654 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE broker (id SERIAL NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE client ADD broker_id INT NOT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404556CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C744045533CBAD07 ON client (client_ref)');
        $this->addSql('CREATE INDEX IDX_C74404556CC064FC ON client (broker_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74404556CC064FC33CBAD07 ON client (broker_id, client_ref)');
        $this->addSql('ALTER TABLE event ADD broker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA76CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA75E237E06 ON event (name)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA76CC064FC ON event (broker_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA76CC064FC5E237E06 ON event (broker_id, name)');
        $this->addSql('ALTER TABLE financials ADD broker_id INT NOT NULL');
        $this->addSql('ALTER TABLE financials ADD CONSTRAINT FK_C400A0626CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C400A0626CC064FC ON financials (broker_id)');
        $this->addSql('ALTER TABLE insurer ADD broker_id INT NOT NULL');
        $this->addSql('ALTER TABLE insurer ADD CONSTRAINT FK_A2DB411F6CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A2DB411F5E237E06 ON insurer (name)');
        $this->addSql('CREATE INDEX IDX_A2DB411F6CC064FC ON insurer (broker_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A2DB411F6CC064FC5E237E06 ON insurer (broker_id, name)');
        $this->addSql('ALTER TABLE policy ADD broker_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D05166CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516BC44F249 ON policy (policy_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516C716E0B8 ON policy (insurer_policy_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516A8D2BF27 ON policy (root_policy_ref)');
        $this->addSql('CREATE INDEX IDX_F07D05166CC064FC ON policy (broker_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D05166CC064FCBC44F249 ON policy (broker_id, policy_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516895854C7C716E0B8 ON policy (insurer_id, insurer_policy_number)');
        $this->addSql('ALTER TABLE product ADD broker_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD6CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD5E237E06 ON product (name)');
        $this->addSql('CREATE INDEX IDX_D34A04AD6CC064FC ON product (broker_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD6CC064FC5E237E06 ON product (broker_id, name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE client DROP CONSTRAINT FK_C74404556CC064FC');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA76CC064FC');
        $this->addSql('ALTER TABLE financials DROP CONSTRAINT FK_C400A0626CC064FC');
        $this->addSql('ALTER TABLE insurer DROP CONSTRAINT FK_A2DB411F6CC064FC');
        $this->addSql('ALTER TABLE policy DROP CONSTRAINT FK_F07D05166CC064FC');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04AD6CC064FC');
        $this->addSql('DROP TABLE broker');
        $this->addSql('DROP INDEX UNIQ_D34A04AD5E237E06');
        $this->addSql('DROP INDEX IDX_D34A04AD6CC064FC');
        $this->addSql('DROP INDEX UNIQ_D34A04AD6CC064FC5E237E06');
        $this->addSql('ALTER TABLE product DROP broker_id');
        $this->addSql('DROP INDEX UNIQ_A2DB411F5E237E06');
        $this->addSql('DROP INDEX IDX_A2DB411F6CC064FC');
        $this->addSql('DROP INDEX UNIQ_A2DB411F6CC064FC5E237E06');
        $this->addSql('ALTER TABLE insurer DROP broker_id');
        $this->addSql('DROP INDEX UNIQ_C744045533CBAD07');
        $this->addSql('DROP INDEX IDX_C74404556CC064FC');
        $this->addSql('DROP INDEX UNIQ_C74404556CC064FC33CBAD07');
        $this->addSql('ALTER TABLE client DROP broker_id');
        $this->addSql('DROP INDEX IDX_C400A0626CC064FC');
        $this->addSql('ALTER TABLE financials DROP broker_id');
        $this->addSql('DROP INDEX UNIQ_F07D0516BC44F249');
        $this->addSql('DROP INDEX UNIQ_F07D0516C716E0B8');
        $this->addSql('DROP INDEX UNIQ_F07D0516A8D2BF27');
        $this->addSql('DROP INDEX IDX_F07D05166CC064FC');
        $this->addSql('DROP INDEX UNIQ_F07D05166CC064FCBC44F249');
        $this->addSql('DROP INDEX UNIQ_F07D0516895854C7C716E0B8');
        $this->addSql('ALTER TABLE policy DROP broker_id');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA75E237E06');
        $this->addSql('DROP INDEX IDX_3BAE0AA76CC064FC');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA76CC064FC5E237E06');
        $this->addSql('ALTER TABLE event DROP broker_id');
    }
}
