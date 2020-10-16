<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191013174523 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP CONSTRAINT fk_d8716ad5d7943d68');
        $this->addSql('DROP INDEX idx_d8716ad5d7943d68');
        $this->addSql('ALTER TABLE servizio ADD ente_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD who TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD special_cases TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD more_info TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD coverage TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio DROP schede_informative');
        $this->addSql('ALTER TABLE servizio DROP custom_texts');
        $this->addSql('ALTER TABLE servizio ADD flow_steps JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio RENAME COLUMN area TO topics');
        $this->addSql('ALTER TABLE servizio RENAME COLUMN testo_istruzioni TO howto');
        $this->addSql('ALTER TABLE servizio ADD CONSTRAINT FK_D8716AD5EFB68F0A FOREIGN KEY (ente_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE servizio ADD CONSTRAINT FK_D8716AD591F64639 FOREIGN KEY (topics) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8716AD5EFB68F0A ON servizio (ente_id)');
        $this->addSql('CREATE INDEX IDX_D8716AD591F64639 ON servizio (topics)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE servizio DROP CONSTRAINT FK_D8716AD5EFB68F0A');
        $this->addSql('ALTER TABLE servizio DROP CONSTRAINT FK_D8716AD591F64639');
        $this->addSql('DROP INDEX IDX_D8716AD5EFB68F0A');
        $this->addSql('DROP INDEX IDX_D8716AD591F64639');
        $this->addSql('ALTER TABLE servizio ADD schede_informative TEXT NOT NULL');
        $this->addSql('ALTER TABLE servizio DROP ente_id');
        $this->addSql('ALTER TABLE servizio RENAME COLUMN howto TO testo_istruzioni');
        $this->addSql('ALTER TABLE servizio RENAME COLUMN topics TO area');
        $this->addSql('ALTER TABLE servizio DROP who');
        $this->addSql('ALTER TABLE servizio DROP special_cases');
        $this->addSql('ALTER TABLE servizio DROP more_info');
        $this->addSql('ALTER TABLE servizio DROP coverage');
        $this->addSql('ALTER TABLE servizio DROP flow_steps');
        $this->addSql('ALTER TABLE servizio ADD custom_texts JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD CONSTRAINT fk_d8716ad5d7943d68 FOREIGN KEY (area) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d8716ad5d7943d68 ON servizio (area)');
    }
}
