<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241221205810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu_menu_item (menu_id INT NOT NULL, menu_item_id INT NOT NULL, PRIMARY KEY(menu_id, menu_item_id))');
        $this->addSql('CREATE INDEX IDX_CE14B264CCD7E912 ON menu_menu_item (menu_id)');
        $this->addSql('CREATE INDEX IDX_CE14B2649AB44FE0 ON menu_menu_item (menu_item_id)');
        $this->addSql('ALTER TABLE menu_menu_item ADD CONSTRAINT FK_CE14B264CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE menu_menu_item ADD CONSTRAINT FK_CE14B2649AB44FE0 FOREIGN KEY (menu_item_id) REFERENCES menu_item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE menu_menu_item DROP CONSTRAINT FK_CE14B264CCD7E912');
        $this->addSql('ALTER TABLE menu_menu_item DROP CONSTRAINT FK_CE14B2649AB44FE0');
        $this->addSql('DROP TABLE menu_menu_item');
    }
}
