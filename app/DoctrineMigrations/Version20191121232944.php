<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191121232944 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio ADD protocollo_parameters JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ALTER coverage TYPE TEXT');
        $this->addSql('ALTER TABLE servizio ALTER coverage DROP DEFAULT');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data TYPE TEXT');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data DROP DEFAULT');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE failure_login_attempt ALTER data TYPE TEXT');
        $this->addSql('ALTER TABLE failure_login_attempt ALTER data DROP DEFAULT');
        $this->addSql('ALTER TABLE servizio DROP protocollo_parameters');
        $this->addSql('ALTER TABLE servizio ALTER coverage TYPE TEXT');
        $this->addSql('ALTER TABLE servizio ALTER coverage DROP DEFAULT');
    }
}
