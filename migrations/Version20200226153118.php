<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200226153118 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE meeting (id UUID NOT NULL, calendar_id UUID NOT NULL, user_id UUID DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(10) DEFAULT NULL, fiscal_code VARCHAR(16) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, from_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, to_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_message TEXT NOT NULL, status INT NOT NULL, rescheduled INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F515E139A40A2C8 ON meeting (calendar_id)');
        $this->addSql('CREATE INDEX IDX_F515E139A76ED395 ON meeting (user_id)');
        $this->addSql('CREATE TABLE calendar (id UUID NOT NULL, owner_id UUID NOT NULL, title VARCHAR(255) NOT NULL, contact_email VARCHAR(255) DEFAULT NULL, rolling_days INT NOT NULL, is_moderated BOOLEAN NOT NULL, location TEXT NOT NULL, closing_periods JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6EA9A1462B36786B ON calendar (title)');
        $this->addSql('CREATE INDEX IDX_6EA9A1467E3C61F9 ON calendar (owner_id)');
        $this->addSql('COMMENT ON COLUMN calendar.closing_periods IS \'(DC2Type:json_array)\'');
        $this->addSql('CREATE TABLE calendars_operators (calendar_id UUID NOT NULL, operator_id UUID NOT NULL, PRIMARY KEY(calendar_id, operator_id))');
        $this->addSql('CREATE INDEX IDX_D636A212A40A2C8 ON calendars_operators (calendar_id)');
        $this->addSql('CREATE INDEX IDX_D636A212584598A3 ON calendars_operators (operator_id)');
        $this->addSql('CREATE TABLE opening_hour (id UUID NOT NULL, calendar_id UUID NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, days_of_week TEXT NOT NULL, begin_hour TIME(0) WITHOUT TIME ZONE NOT NULL, end_hour TIME(0) WITHOUT TIME ZONE NOT NULL, meeting_minutes INT DEFAULT 30 NOT NULL, meeting_queue INT DEFAULT 1 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_969BD765A40A2C8 ON opening_hour (calendar_id)');
        $this->addSql('COMMENT ON COLUMN opening_hour.days_of_week IS \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE meeting ADD CONSTRAINT FK_F515E139A40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE meeting ADD CONSTRAINT FK_F515E139A76ED395 FOREIGN KEY (user_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE calendar ADD CONSTRAINT FK_6EA9A1467E3C61F9 FOREIGN KEY (owner_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE calendars_operators ADD CONSTRAINT FK_D636A212A40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE calendars_operators ADD CONSTRAINT FK_D636A212584598A3 FOREIGN KEY (operator_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE opening_hour ADD CONSTRAINT FK_969BD765A40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
     }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE meeting DROP CONSTRAINT FK_F515E139A40A2C8');
        $this->addSql('ALTER TABLE calendars_operators DROP CONSTRAINT FK_D636A212A40A2C8');
        $this->addSql('ALTER TABLE opening_hour DROP CONSTRAINT FK_969BD765A40A2C8');
        $this->addSql('DROP TABLE meeting');
        $this->addSql('DROP TABLE calendar');
        $this->addSql('DROP TABLE calendars_operators');
        $this->addSql('DROP TABLE opening_hour');
    }
}
