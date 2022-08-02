<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210426123756 extends AbstractMigration
{
    public function up(Schema $schema): void : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE opening_hour ADD is_moderated BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE calendar ADD allow_overlaps BOOLEAN DEFAULT \'false\' NOT NULL');
        $this->addSql('ALTER TABLE meeting ALTER user_message DROP NOT NULL');
        $this->addSql('UPDATE opening_hour SET is_moderated = calendar.is_moderated FROM calendar WHERE opening_hour.calendar_id = calendar.id');
    }

    public function down(Schema $schema): void : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE opening_hour DROP is_moderated');
        $this->addSql('ALTER TABLE calendar DROP allow_overlaps');
        $this->addSql('UPDATE meeting SET user_message = \'non disponibile\' WHERE meeting.user_message is null');
        $this->addSql('ALTER TABLE meeting ALTER user_message SET NOT NULL');

    }
}
