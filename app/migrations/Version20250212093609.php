<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250212093609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D051619EB6921');
        $this->addSql('CREATE TABLE broker_client (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, client_ref VARCHAR(50) NOT NULL, client_type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_181BC52B6CC064FC (broker_id), UNIQUE INDEX UNIQ_181BC52B6CC064FC33CBAD07 (broker_id, client_ref), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE broker_client ADD CONSTRAINT FK_181BC52B6CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_C74404556CC064FC');
        $this->addSql('DROP TABLE client');
        $this->addSql('ALTER TABLE broker ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F6AAF03BD17F50A6 ON broker (uuid)');
        $this->addSql('DROP INDEX UNIQ_BD5EE02D17F50A6 ON broker_config');
        $this->addSql('ALTER TABLE broker_config DROP uuid');
        $this->addSql('DROP INDEX UNIQ_3BAE0AA75E237E06 ON event');
        $this->addSql('DROP INDEX UNIQ_A2DB411F5E237E06 ON insurer');
        $this->addSql('DROP INDEX IDX_F07D051619EB6921 ON policy');
        $this->addSql('ALTER TABLE policy CHANGE client_id broker_client_id INT NOT NULL');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D05166EB186CE FOREIGN KEY (broker_client_id) REFERENCES broker_client (id)');
        $this->addSql('CREATE INDEX IDX_F07D05166EB186CE ON policy (broker_client_id)');
        $this->addSql('CREATE INDEX IDX_F07D051695275AB8 ON policy (start_date)');
        $this->addSql('CREATE INDEX IDX_F07D0516845CBB3E ON policy (end_date)');
        $this->addSql('DROP INDEX UNIQ_D34A04AD5E237E06 ON product');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE policy DROP FOREIGN KEY FK_F07D05166EB186CE');
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, client_ref VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, client_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_C74404556CC064FC33CBAD07 (broker_id, client_ref), INDEX IDX_C74404556CC064FC (broker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_C74404556CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
        $this->addSql('ALTER TABLE broker_client DROP FOREIGN KEY FK_181BC52B6CC064FC');
        $this->addSql('DROP TABLE broker_client');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA75E237E06 ON event (name)');
        $this->addSql('ALTER TABLE broker_config ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BD5EE02D17F50A6 ON broker_config (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD5E237E06 ON product (name)');
        $this->addSql('DROP INDEX IDX_F07D05166EB186CE ON policy');
        $this->addSql('DROP INDEX IDX_F07D051695275AB8 ON policy');
        $this->addSql('DROP INDEX IDX_F07D0516845CBB3E ON policy');
        $this->addSql('ALTER TABLE policy CHANGE broker_client_id client_id INT NOT NULL');
        $this->addSql('ALTER TABLE policy ADD CONSTRAINT FK_F07D051619EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('CREATE INDEX IDX_F07D051619EB6921 ON policy (client_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A2DB411F5E237E06 ON insurer (name)');
        $this->addSql('DROP INDEX UNIQ_F6AAF03BD17F50A6 ON broker');
        $this->addSql('ALTER TABLE broker DROP uuid');
    }
}
