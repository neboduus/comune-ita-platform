<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221013164610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_group ADD constraints TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD conditions TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD life_events JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD business_events JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD costs_attachments JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD conditions_attachments JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD times_and_deadlines TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_group ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT LOCALTIMESTAMP');
        $this->addSql('ALTER TABLE service_group ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT LOCALTIMESTAMP');
        $this->addSql('ALTER TABLE service_group ADD external_card_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD constraints TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD conditions TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD life_events JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD business_events JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD conditions_attachments JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD costs_attachments JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD times_and_deadlines TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD booking_call_to_action VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE servizio ADD external_card_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE servizio DROP constraints');
        $this->addSql('ALTER TABLE servizio DROP conditions');
        $this->addSql('ALTER TABLE servizio DROP life_events');
        $this->addSql('ALTER TABLE servizio DROP business_events');
        $this->addSql('ALTER TABLE servizio DROP conditions_attachments');
        $this->addSql('ALTER TABLE servizio DROP costs_attachments');
        $this->addSql('ALTER TABLE servizio DROP times_and_deadlines');
        $this->addSql('ALTER TABLE servizio DROP booking_call_to_action');
        $this->addSql('ALTER TABLE servizio DROP external_card_url');
        $this->addSql('ALTER TABLE service_group DROP constraints');
        $this->addSql('ALTER TABLE service_group DROP conditions');
        $this->addSql('ALTER TABLE service_group DROP life_events');
        $this->addSql('ALTER TABLE service_group DROP business_events');
        $this->addSql('ALTER TABLE service_group DROP costs_attachments');
        $this->addSql('ALTER TABLE service_group DROP conditions_attachments');
        $this->addSql('ALTER TABLE service_group DROP times_and_deadlines');
        $this->addSql('ALTER TABLE service_group DROP created_at');
        $this->addSql('ALTER TABLE service_group DROP updated_at');
        $this->addSql('ALTER TABLE service_group DROP external_card_url');

    }
}
