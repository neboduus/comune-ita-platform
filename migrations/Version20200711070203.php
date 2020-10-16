<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200711070203 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP email_text');
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, '%motivazione%', '%messaggio_personale%')::json)");
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, 'Con la seguente motivazione:', 'Con il seguente messaggio:')::json)");
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, 'La ringraziamo per aver utilizzato la stanza del cittadino.', 'La ringraziamo per la collaborazione.')::json)");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD email_text TEXT DEFAULT NULL');
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, '%messaggio_personale%', '%motivazione%')::json)");
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, 'Con il seguente messaggio:', 'Con la seguente motivazione:')::json)");
        $this->addSql("UPDATE servizio SET feedback_messages = (REPLACE(feedback_messages::text, 'La ringraziamo per la collaborazione.', 'La ringraziamo per aver utilizzato la stanza del cittadino.')::json)");

    }
}
