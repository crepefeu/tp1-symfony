<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241221205811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix active column in table entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "table" DROP COLUMN IF EXISTS is_active');
        $this->addSql('ALTER TABLE "table" DROP COLUMN IF EXISTS active');
        $this->addSql('ALTER TABLE "table" ADD active BOOLEAN NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "table" DROP COLUMN active');
    }
}
