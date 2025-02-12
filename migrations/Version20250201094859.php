<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201094859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_F07D0516BC44F249 ON policy');
        $this->addSql('DROP INDEX UNIQ_F07D0516A8D2BF27 ON policy');
        $this->addSql('DROP INDEX UNIQ_F07D0516C716E0B8 ON policy');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516BC44F249 ON policy (policy_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516A8D2BF27 ON policy (root_policy_ref)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F07D0516C716E0B8 ON policy (insurer_policy_number)');
    }
}
