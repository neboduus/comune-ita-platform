<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200331081006 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE meeting ADD opening_hour_id UUID');
        $this->addSql('ALTER TABLE meeting ADD videoconference_link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE meeting ADD CONSTRAINT FK_F515E13981F9D579 FOREIGN KEY (opening_hour_id) REFERENCES opening_hour (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F515E13981F9D579 ON meeting (opening_hour_id)');
        $this->addSql('ALTER TABLE calendar ADD minimum_scheduling_notice INT');
        $this->addSql('ALTER TABLE opening_hour ADD interval_minutes INT DEFAULT 0');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE opening_hour DROP interval_minutes');
        $this->addSql('ALTER TABLE calendar DROP minimum_scheduling_notice');
        $this->addSql('ALTER TABLE meeting DROP CONSTRAINT FK_F515E13981F9D579');
        $this->addSql('DROP INDEX IDX_F515E13981F9D579');
        $this->addSql('ALTER TABLE meeting DROP opening_hour_id');
        $this->addSql('ALTER TABLE meeting DROP videoconference_link');
    }
}
