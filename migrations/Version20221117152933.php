<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221117152933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_group (id UUID NOT NULL, topic_id UUID DEFAULT NULL, manager_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, short_description VARCHAR(255) DEFAULT NULL, main_function TEXT DEFAULT NULL, more_info TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F02BF9D1F55203D ON user_group (topic_id)');
        $this->addSql('CREATE INDEX IDX_8F02BF9D783E3463 ON user_group (manager_id)');
        $this->addSql('CREATE TABLE user_group_servizio (user_group_id UUID NOT NULL, servizio_id UUID NOT NULL, PRIMARY KEY(user_group_id, servizio_id))');
        $this->addSql('CREATE INDEX IDX_BAC4F9F31ED93D47 ON user_group_servizio (user_group_id)');
        $this->addSql('CREATE INDEX IDX_BAC4F9F35513F0B4 ON user_group_servizio (servizio_id)');
        $this->addSql('CREATE TABLE user_group_operatore_user (user_group_id UUID NOT NULL, operatore_user_id UUID NOT NULL, PRIMARY KEY(user_group_id, operatore_user_id))');
        $this->addSql('CREATE INDEX IDX_2EEE959F1ED93D47 ON user_group_operatore_user (user_group_id)');
        $this->addSql('CREATE INDEX IDX_2EEE959FBC1F0D9E ON user_group_operatore_user (operatore_user_id)');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D1F55203D FOREIGN KEY (topic_id) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9D783E3463 FOREIGN KEY (manager_id) REFERENCES utente (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_servizio ADD CONSTRAINT FK_BAC4F9F31ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_servizio ADD CONSTRAINT FK_BAC4F9F35513F0B4 FOREIGN KEY (servizio_id) REFERENCES servizio (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_operatore_user ADD CONSTRAINT FK_2EEE959F1ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_operatore_user ADD CONSTRAINT FK_2EEE959FBC1F0D9E FOREIGN KEY (operatore_user_id) REFERENCES utente (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_group_servizio DROP CONSTRAINT FK_BAC4F9F31ED93D47');
        $this->addSql('ALTER TABLE user_group_operatore_user DROP CONSTRAINT FK_2EEE959F1ED93D47');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE user_group_servizio');
        $this->addSql('DROP TABLE user_group_operatore_user');
    }
}
