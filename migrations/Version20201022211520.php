<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201022211520 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE failure_login_attempt ADD username VARCHAR(255) DEFAULT NULL');

        $this->addSql("UPDATE payment_gateway set fcqn = REPLACE(fcqn, 'AppBundle', 'App' )");
        $this->addSql("UPDATE servizio set pratica_fcqn = REPLACE(pratica_fcqn, 'AppBundle', 'App' );");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE failure_login_attempt DROP username');

        $this->addSql("UPDATE payment_gateway set fcqn = REPLACE(fcqn, 'App', 'AppBundle' )");
        $this->addSql("UPDATE servizio set pratica_fcqn = REPLACE(pratica_fcqn, 'App', 'AppBundle' );");
    }
}
