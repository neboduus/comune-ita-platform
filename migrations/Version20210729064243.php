<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210729064243 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_group ADD howto TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD who TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD special_cases TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD more_info TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD coverage TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD shared_with_group BOOLEAN DEFAULT \'false\'');
        $this->addSql('COMMENT ON COLUMN service_group.coverage IS \'(DC2Type:array)\'');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE service_group DROP howto');
        $this->addSql('ALTER TABLE service_group DROP who');
        $this->addSql('ALTER TABLE service_group DROP special_cases');
        $this->addSql('ALTER TABLE service_group DROP more_info');
        $this->addSql('ALTER TABLE service_group DROP coverage');
        $this->addSql('ALTER TABLE servizio DROP shared_with_group');
    }
}
