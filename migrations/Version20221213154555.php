<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221213154555 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE place (id UUID NOT NULL, topic_id UUID DEFAULT NULL, core_contact_point_id UUID DEFAULT NULL, name VARCHAR(255) NOT NULL, other_name VARCHAR(255) DEFAULT NULL, short_description VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, address JSON DEFAULT NULL, latitude VARCHAR(255) DEFAULT NULL, longitude VARCHAR(255) DEFAULT NULL, more_info TEXT DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_741D53CD1F55203D ON place (topic_id)');
        $this->addSql('CREATE INDEX IDX_741D53CD3FC07731 ON place (core_contact_point_id)');
        $this->addSql('CREATE TABLE place_geographic_area (place_id UUID NOT NULL, geographic_area_id UUID NOT NULL, PRIMARY KEY(place_id, geographic_area_id))');
        $this->addSql('CREATE INDEX IDX_F6337A68DA6A219 ON place_geographic_area (place_id)');
        $this->addSql('CREATE INDEX IDX_F6337A687A617856 ON place_geographic_area (geographic_area_id)');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD1F55203D FOREIGN KEY (topic_id) REFERENCES categoria (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE place ADD CONSTRAINT FK_741D53CD3FC07731 FOREIGN KEY (core_contact_point_id) REFERENCES contact_point (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE place_geographic_area ADD CONSTRAINT FK_F6337A68DA6A219 FOREIGN KEY (place_id) REFERENCES place (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE place_geographic_area ADD CONSTRAINT FK_F6337A687A617856 FOREIGN KEY (geographic_area_id) REFERENCES geographic_area (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group ADD core_location_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE user_group ADD CONSTRAINT FK_8F02BF9DD17CBFE4 FOREIGN KEY (core_location_id) REFERENCES place (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8F02BF9DD17CBFE4 ON user_group (core_location_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE place_geographic_area DROP CONSTRAINT FK_F6337A68DA6A219');
        $this->addSql('ALTER TABLE user_group DROP CONSTRAINT FK_8F02BF9DD17CBFE4');
        $this->addSql('DROP TABLE place');
        $this->addSql('DROP TABLE place_geographic_area');
        $this->addSql('DROP INDEX IDX_8F02BF9DD17CBFE4');
        $this->addSql('ALTER TABLE user_group DROP core_location_id');
    }
}
