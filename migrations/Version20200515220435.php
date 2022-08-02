<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515220435 extends AbstractMigration
{
    public function up(Schema $schema):void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente ADD spid_code TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD shib_session_id TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD shib_session_index TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE utente ADD shib_auth_instant TEXT DEFAULT NULL');
    }

    public function down(Schema $schema):void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE utente DROP spid_code');
        $this->addSql('ALTER TABLE utente DROP shib_session_id');
        $this->addSql('ALTER TABLE utente DROP shib_session_index');
        $this->addSql('ALTER TABLE utente DROP shib_auth_instant');
    }
}
