<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220803131855 extends AbstractMigration
{
  public function getDescription(): string
  {
    return '';
  }

  public function up(Schema $schema): void
  {
    // this up() migration is auto-generated, please modify it to your needs
    $this->addSql('ALTER TABLE ext_translations ALTER object_class TYPE VARCHAR(191)');
    $this->addSql('CREATE INDEX general_translations_lookup_idx ON ext_translations (object_class, foreign_key)');
    $this->addSql('ALTER TABLE failure_login_attempt ADD username VARCHAR(255) DEFAULT NULL');
    $this->addSql("UPDATE utente set created_at = NOW() WHERE created_at IS NULL");
    $this->addSql("UPDATE utente set updated_at = NOW() WHERE updated_at IS NULL");
    $this->addSql('ALTER TABLE utente ALTER created_at SET NOT NULL');
    $this->addSql('ALTER TABLE utente ALTER updated_at SET NOT NULL');
    $this->addSql('CREATE UNIQUE INDEX UNIQ_DE45B3E0F85E0677 ON utente (username)');
    $this->addSql("UPDATE servizio SET pratica_fcqn = REPLACE(pratica_fcqn, 'AppBundle', 'App')");
    $this->addSql("UPDATE servizio SET integrations = REPLACE(integrations::TEXT,'AppBundle','App')::JSON");
  }

  public function down(Schema $schema): void
  {
    // this down() migration is auto-generated, please modify it to your needs
    $this->addSql('ALTER TABLE failure_login_attempt DROP username');
    $this->addSql('DROP INDEX UNIQ_DE45B3E0F85E0677');
    $this->addSql('ALTER TABLE utente ALTER created_at DROP NOT NULL');
    $this->addSql('ALTER TABLE utente ALTER updated_at DROP NOT NULL');
    $this->addSql('DROP INDEX general_translations_lookup_idx');
    $this->addSql('ALTER TABLE ext_translations ALTER object_class TYPE VARCHAR(255)');
    $this->addSql("UPDATE servizio SET pratica_fcqn = REPLACE(pratica_fcqn, 'App', 'AppBundle')");
    $this->addSql("UPDATE servizio SET integrations = REPLACE(integrations::TEXT,'App','AppBundle')::JSON");
  }
}
