<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220629125539 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD how_to_do TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD what_you_need TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD what_you_get TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD costs TEXT DEFAULT NULL');

        $this->addSql('ALTER TABLE service_group ADD how_to_do TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD what_you_need TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD what_you_get TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD costs TEXT DEFAULT NULL');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP how_to_do');
        $this->addSql('ALTER TABLE servizio DROP what_you_need');
        $this->addSql('ALTER TABLE servizio DROP what_you_get');
        $this->addSql('ALTER TABLE servizio DROP costs');

        $this->addSql('ALTER TABLE service_group DROP how_to_do');
        $this->addSql('ALTER TABLE service_group DROP what_you_need');
        $this->addSql('ALTER TABLE service_group DROP what_you_get');
        $this->addSql('ALTER TABLE service_group DROP costs');
    }
}
