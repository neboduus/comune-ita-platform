<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221229084055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE categoria SET name = 'Tributi, finanze e contravvenzioni', slug = 'tributi-finanze-e-contravvenzioni', description = 'Tributi, finanze e contravvenzioni - dichiarazione redditi, contributi' WHERE name = 'Tributi e finanze'");
        $this->addSql("UPDATE categoria SET name = 'Impresa e commercio', slug = 'impresa-e-commercio', description = 'Impresa e commercio - attività produttive, impresa nazionale/estera, notifiche, bancarotta, risorse umane' WHERE name = 'Attività produttive e commercio'");
        $this->addSql("UPDATE categoria SET name = 'Agricoltura e pesca', slug = 'agricoltura-e-pesca', description = 'Agricoltura e pesca - politiche agricole e alimentari' WHERE name = 'Agricoltura'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE categoria SET name = 'Tributi e finanze', slug = 'tributi-e-finanze', description = 'Tributi e finanze - dichiarazione redditi, contributi, contravvenzioni' WHERE name = 'Tributi, finanze e contravvenzioni'");
        $this->addSql("UPDATE categoria SET name = 'Attività produttive e commercio', slug = 'attivita-produttive-e-commercio', description = 'Attività produttive e commercio - impresa nazionale/estera, notifiche, bancarotta, risorse umane' WHERE name = 'Impresa e commercio'");
        $this->addSql("UPDATE categoria SET name = 'Agricoltura', slug = 'agricoltura', description = 'Agricoltura - pesca, politiche agricole e alimentari' WHERE name = 'Agricoltura e pesca'");
    }       
}
