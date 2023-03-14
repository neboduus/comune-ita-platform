<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230307095232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE servizio SET topics = COALESCE((SELECT id FROM categoria WHERE slug = 'catasto-e-urbanistica' LIMIT 1), (SELECT id FROM categoria LIMIT 1))
                           WHERE slug IN ( 'autorizzazione-paesaggistica-sindaco', 'comunicazione-inizio-lavori', 'comunicazione-inizio-lavori-asseverata',
                                           'domanda-permesso-di-costruire', 'dichiarazione-ultimazione-lavori', 'comunicazione-opere-libere',
                                           'domanda-permesso-di-costruire-in-sanatoria', 'scia-pratica-edilizia','segnalazione-certificata-di-agibilita')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
