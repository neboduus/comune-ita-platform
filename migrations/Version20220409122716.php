<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220409122716 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE pratica ALTER updated_at DROP DEFAULT');
        $this->addSql('ALTER TABLE meeting ADD first_available_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE meeting ADD first_available_start_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE meeting ADD first_available_end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE meeting ADD first_availability_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE pratica ALTER created_at SET DEFAULT \'1970-01-01 00:00:00\'');
        $this->addSql('ALTER TABLE pratica ALTER updated_at SET DEFAULT \'1970-01-01 00:00:00\'');
        $this->addSql('ALTER TABLE meeting DROP first_available_date');
        $this->addSql('ALTER TABLE meeting DROP first_available_start_time');
        $this->addSql('ALTER TABLE meeting DROP first_available_end_time');
        $this->addSql('ALTER TABLE meeting DROP first_availability_updated_at');

    }
}
