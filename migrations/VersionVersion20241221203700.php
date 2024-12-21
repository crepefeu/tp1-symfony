<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class VersionVersion20241221203700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Update existing records to set active = true
        $this->addSql('UPDATE "table" SET active = true WHERE active IS NULL');
        
        // Make the column not nullable
        $this->addSql('ALTER TABLE "table" ALTER COLUMN active SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "table" ALTER COLUMN active DROP NOT NULL');
    }
}
