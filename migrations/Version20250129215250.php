<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250129215250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE policy (id SERIAL NOT NULL, client_id INT NOT NULL, insurer_id INT NOT NULL, product_id INT NOT NULL, event_id INT NOT NULL, policy_number VARCHAR(50) NOT NULL, insurer_policy_number VARCHAR(50) NOT NULL, root_policy_ref VARCHAR(50) NOT NULL, policy_type VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, effective_date DATE NOT NULL, renewal_date DATE NOT NULL, company_description VARCHAR(140) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F07D051619EB6921 ON policy (client_id)');
        $this->addSql('CREATE INDEX IDX_F07D0516895854C7 ON policy (insurer_id)');
        $this->addSql('CREATE INDEX IDX_F07D05164584665A ON policy (product_id)');
        $this->addSql('CREATE INDEX IDX_F07D051671F7E88B ON policy (event_id)');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D0516895854C7 FOREIGN KEY (insurer_id) REFERENCES insurer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D05164584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051671F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE policy DROP CONSTRAINT FK_F07D051619EB6921');
        $this->addSql('ALTER TABLE policy DROP CONSTRAINT FK_F07D0516895854C7');
        $this->addSql('ALTER TABLE policy DROP CONSTRAINT FK_F07D05164584665A');
        $this->addSql('ALTER TABLE policy DROP CONSTRAINT FK_F07D051671F7E88B');
        $this->addSql('DROP TABLE policy');
    }
}
