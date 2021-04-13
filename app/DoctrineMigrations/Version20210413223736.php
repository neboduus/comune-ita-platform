<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210413223736 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE lexik_trans_unit (id SERIAL NOT NULL, key_name VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX key_domain_idx ON lexik_trans_unit (key_name, domain)');
        $this->addSql('CREATE TABLE lexik_trans_unit_translations (id SERIAL NOT NULL, file_id INT DEFAULT NULL, trans_unit_id INT DEFAULT NULL, locale VARCHAR(10) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, modified_manually BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B0AA394493CB796C ON lexik_trans_unit_translations (file_id)');
        $this->addSql('CREATE INDEX IDX_B0AA3944C3C583C9 ON lexik_trans_unit_translations (trans_unit_id)');
        $this->addSql('CREATE UNIQUE INDEX trans_unit_locale_idx ON lexik_trans_unit_translations (trans_unit_id, locale)');
        $this->addSql('CREATE TABLE lexik_translation_file (id SERIAL NOT NULL, domain VARCHAR(255) NOT NULL, locale VARCHAR(10) NOT NULL, extention VARCHAR(10) NOT NULL, path VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX hash_idx ON lexik_translation_file (hash)');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA394493CB796C FOREIGN KEY (file_id) REFERENCES lexik_translation_file (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations ADD CONSTRAINT FK_B0AA3944C3C583C9 FOREIGN KEY (trans_unit_id) REFERENCES lexik_trans_unit (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP CONSTRAINT FK_B0AA3944C3C583C9');
        $this->addSql('ALTER TABLE lexik_trans_unit_translations DROP CONSTRAINT FK_B0AA394493CB796C');
        $this->addSql('DROP TABLE lexik_trans_unit');
        $this->addSql('DROP TABLE lexik_trans_unit_translations');
        $this->addSql('DROP TABLE lexik_translation_file');
    }
}
