<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200409120402 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE document (id UUID NOT NULL, owner_id UUID NOT NULL, folder_id UUID NOT NULL, tenant_id UUID NOT NULL, recipient_type VARCHAR(255) NOT NULL, version INT NOT NULL, md5 VARCHAR(255) DEFAULT NULL, original_filename VARCHAR(255) NOT NULL, mimeType VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, download_link VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, readersAllowed JSON DEFAULT NULL, lastReadAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, validityBegin TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, validityEnd TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expireAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, dueDate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8698A767E3C61F9 ON document (owner_id)');
        $this->addSql('CREATE INDEX IDX_D8698A76162CB942 ON document (folder_id)');
        $this->addSql('CREATE INDEX IDX_D8698A769033212A ON document (tenant_id)');
        $this->addSql('CREATE TABLE documents_topics (document_id UUID NOT NULL, categoria_id UUID NOT NULL, PRIMARY KEY(document_id, categoria_id))');
        $this->addSql('CREATE INDEX IDX_6273707FC33F7837 ON documents_topics (document_id)');
        $this->addSql('CREATE INDEX IDX_6273707F3397707A ON documents_topics (categoria_id)');
        $this->addSql('CREATE TABLE document_services (document_id UUID NOT NULL, service_id UUID NOT NULL, PRIMARY KEY(document_id, service_id))');
        $this->addSql('CREATE INDEX IDX_FCF31030C33F7837 ON document_services (document_id)');
        $this->addSql('CREATE INDEX IDX_FCF31030ED5CA9E6 ON document_services (service_id)');
        $this->addSql('CREATE TABLE folder (id UUID NOT NULL, owner_id UUID NOT NULL, tenant_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_ECA209CD7E3C61F9 ON folder (owner_id)');
        $this->addSql('CREATE INDEX IDX_ECA209CD9033212A ON folder (tenant_id)');
        $this->addSql('CREATE UNIQUE INDEX title_unique ON folder (owner_id, title)');
        $this->addSql('CREATE TABLE folders_services (folder_id UUID NOT NULL, service_id UUID NOT NULL, PRIMARY KEY(folder_id, service_id))');
        $this->addSql('CREATE INDEX IDX_4885F992162CB942 ON folders_services (folder_id)');
        $this->addSql('CREATE INDEX IDX_4885F992ED5CA9E6 ON folders_services (service_id)');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A767E3C61F9 FOREIGN KEY (owner_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A769033212A FOREIGN KEY (tenant_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE documents_topics ADD CONSTRAINT FK_6273707FC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE documents_topics ADD CONSTRAINT FK_6273707F3397707A FOREIGN KEY (categoria_id) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_services ADD CONSTRAINT FK_FCF31030C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE document_services ADD CONSTRAINT FK_FCF31030ED5CA9E6 FOREIGN KEY (service_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE folder ADD CONSTRAINT FK_ECA209CD9033212A FOREIGN KEY (tenant_id) REFERENCES ente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE folders_services ADD CONSTRAINT FK_4885F992162CB942 FOREIGN KEY (folder_id) REFERENCES folder (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE folders_services ADD CONSTRAINT FK_4885F992ED5CA9E6 FOREIGN KEY (service_id) REFERENCES servizio (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE documents_topics DROP CONSTRAINT FK_6273707FC33F7837');
        $this->addSql('ALTER TABLE document_services DROP CONSTRAINT FK_FCF31030C33F7837');
        $this->addSql('ALTER TABLE document DROP CONSTRAINT FK_D8698A76162CB942');
        $this->addSql('ALTER TABLE folders_services DROP CONSTRAINT FK_4885F992162CB942');
        $this->addSql('DROP TABLE document');
        $this->addSql('DROP TABLE documents_topics');
        $this->addSql('DROP TABLE document_services');
        $this->addSql('DROP TABLE folder');
        $this->addSql('DROP TABLE folders_services');
    }
}
