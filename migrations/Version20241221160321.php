<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241221160321 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Update existing null values to 'pending'
        $this->addSql("UPDATE reservation SET status = 'pending' WHERE status IS NULL");
        
        // Alter the column to use enum type and set not null constraint
        $this->addSql("ALTER TABLE reservation ALTER COLUMN status TYPE VARCHAR(50)");
        $this->addSql("ALTER TABLE reservation ALTER COLUMN status SET NOT NULL");
        $this->addSql("ALTER TABLE reservation ALTER COLUMN status SET DEFAULT 'pending'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE reservation ALTER COLUMN status DROP DEFAULT");
        $this->addSql("ALTER TABLE reservation ALTER COLUMN status DROP NOT NULL");
    }
}
