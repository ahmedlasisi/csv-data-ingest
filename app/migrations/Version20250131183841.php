<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250131183841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE broker_config (id INT AUTO_INCREMENT NOT NULL, broker_id INT NOT NULL, file_name VARCHAR(50) NOT NULL, file_mapping JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_BD5EE02D7DF1668 (file_name), UNIQUE INDEX UNIQ_BD5EE026CC064FC (broker_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE broker_config ADD CONSTRAINT FK_BD5EE026CC064FC FOREIGN KEY (broker_id) REFERENCES broker (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE broker_config DROP FOREIGN KEY FK_BD5EE026CC064FC');
        $this->addSql('DROP TABLE broker_config');
    }
}
