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
        $this->addSql("UPDATE categoria SET name = 'Tributi, finanze e contravvenzioni', slug = 'tributi-finanze-e-contravvenzioni', description = 'Tributi, finanze e contravvenzioni - dichiarazione redditi, contributi' WHERE slug = 'tributi-e-finanze'");
        $this->addSql("UPDATE categoria SET name = 'Impresa e commercio', slug = 'impresa-e-commercio', description = 'Impresa e commercio - attività produttive, impresa nazionale/estera, notifiche, bancarotta, risorse umane' WHERE slug = 'attivita-produttive-e-commercio'");
        $this->addSql("UPDATE categoria SET name = 'Agricoltura e pesca', slug = 'agricoltura-e-pesca', description = 'Autorizzazioni e politiche alimentari per agricoltura e pesca.' WHERE slug = 'agricoltura'");
        $this->addSql("UPDATE categoria SET name = 'Catasto e urbanistica', slug = 'catasto-e-urbanistica', description = 'Piani urbanistici, piani paesaggistici e tutti i certificati per immobili, case, terreni ed edifici.' WHERE slug = 'edilizia-e-urbanistica'");
        $this->addSql("UPDATE categoria SET description = 'Aree verdi e parchi, inquinamento, igiene urbana e rifiuti.' WHERE slug = 'ambiente'");
        $this->addSql("UPDATE categoria SET description = 'Documenti d’identità, cambio di residenza, servizi elettorali, cimiteriali e certificati per nascita, matrimoni e unioni civili.' WHERE slug = 'anagrafe-e-stato-civile'");
        $this->addSql("UPDATE categoria SET description = 'Gare d’appalto e avvisi per lavori, servizi e forniture al Comune.' WHERE slug = 'appalti-pubblici'");
        $this->addSql("UPDATE categoria SET description = 'Autorizzazioni, permessi, licenze, concessioni di suolo, passi carrabili e prestito di beni del Comune.' WHERE slug = 'autorizzazioni'");
        $this->addSql("UPDATE categoria SET description = 'Luoghi della cultura e dell’arte, impianti sportivi e richieste di contributi per la cultura, lo spettacolo e lo sport.' WHERE slug = 'cultura-e-tempo-libero'");
        $this->addSql("UPDATE categoria SET description = 'Iscrizioni, agevolazioni e servizi per nidi, scuole e università.' WHERE slug = 'educazione-e-formazione'");
        $this->addSql("UPDATE categoria SET description = 'Polizia municipale, tribunale e Protezione civile.' WHERE slug = 'giustizia-e-sicurezza-pubblica'");
        $this->addSql("UPDATE categoria SET description = 'Avvio di un’attività, commercio, autorizzazioni e concessioni per attività produttive, mercati, incentivi e supporto alle imprese.' WHERE slug = 'impresa-e-commercio'");
        $this->addSql("UPDATE categoria SET description = 'Parcheggi, viabilità, automobili e trasporto pubblico.' WHERE slug = 'mobilita-e-trasporti'");
        $this->addSql("UPDATE categoria SET description = 'Servizi sanitari e di sostegno per minori, famiglie, anziani e persone con disabilità.' WHERE slug = 'salute-benessere-e-assistenza'");
        $this->addSql("UPDATE categoria SET description = 'Sostegno e sviluppo del turismo, strutture ricettive e informazioni turistiche.' WHERE slug = 'turismo'");
        $this->addSql("UPDATE categoria SET description = 'Lavoro, concorsi e selezioni, licenze, abilitazioni professionali e sicurezza sul lavoro.' WHERE slug = 'vita-lavorativa'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE categoria SET name = 'Tributi e finanze', slug = 'tributi-e-finanze', description = 'Tributi e finanze - dichiarazione redditi, contributi, contravvenzioni' WHERE slug = 'tributi-finanze-e-contravvenzioni'");
        $this->addSql("UPDATE categoria SET name = 'Attività produttive e commercio', slug = 'attivita-produttive-e-commercio', description = 'Attività produttive e commercio - impresa nazionale/estera, notifiche, bancarotta, risorse umane' WHERE slug = 'impresa-e-commercio'");
        $this->addSql("UPDATE categoria SET name = 'Agricoltura', slug = 'agricoltura', description = 'Agricoltura - pesca, politiche agricole e alimentari' WHERE slug = 'agricoltura-e-pesca'");
        $this->addSql("UPDATE categoria SET name = 'Edilizia e urbanistica', slug = 'edilizia-e-urbanistica', description = 'Piani urbanistici, piani paesaggistici, certificati per immobili, case, terreni, edifici, compatibilità e vincoli paesaggistici, conformità, destinazione d’uso.' WHERE slug = 'catasto-e-urbanistica'");
        $this->addSql("UPDATE categoria SET description = 'Ambiente - rifiuti, verde urbano, incendi' WHERE slug = 'ambiente'");
        $this->addSql("UPDATE categoria SET description = 'Anagrafe e stato civile - residenza, matrimonio, nascita, morte, espatrio, elezioni' WHERE slug = 'anagrafe-e-stato-civile'");
        $this->addSql("UPDATE categoria SET description = 'Appalti pubblici - gare nazionali/estere, bandi' WHERE slug = 'appalti-pubblici'");
        $this->addSql("UPDATE categoria SET description = 'Autorizzazioni - permessi, licenze, finanziamenti' WHERE slug = 'autorizzazioni'");
        $this->addSql("UPDATE categoria SET description = 'Cultura e tempo libero - luoghi della cultura, impianti sportivi' WHERE slug = 'cultura-e-tempo-libero'");
        $this->addSql("UPDATE categoria SET description = 'Educazione e formazione - nido, scuola, università' WHERE slug = 'educazione-e-formazione'");
        $this->addSql("UPDATE categoria SET description = 'Giustizia e sicurezza pubblica - crimini, protezione civile' WHERE slug = 'giustizia-e-sicurezza-pubblica'");
        $this->addSql("UPDATE categoria SET description = 'Impresa e commercio - attività produttive, impresa nazionale/estera, notifiche, bancarotta, risorse umane' WHERE slug = 'impresa-e-commercio'");
        $this->addSql("UPDATE categoria SET description = 'Mobilità e trasporti - parcheggi, automobile, patente, trasporto pubblico' WHERE slug = 'mobilita-e-trasporti'");
        $this->addSql("UPDATE categoria SET description = 'Salute, benessere e assistenza - animali, invalidità, esami sanitari, anziani' WHERE slug = 'salute-benessere-e-assistenza'");
        $this->addSql("UPDATE categoria SET description = 'Turismo - viaggi, passaporto, visto' WHERE slug = 'turismo'");
        $this->addSql("UPDATE categoria SET description = 'Vita lavorativa - lavoro, disoccupazione, pensione' WHERE slug = 'vita-lavorativa'");
    }
}
