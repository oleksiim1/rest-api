<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Product names are unique regardless of case ("Potato" and "potato" are the same product),
 * so the unique index is on LOWER(name). The ORM mapping cannot express an expression index,
 * so this index lives only in migrations.
 */
final class Version20261109230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the products name unique index case-insensitive';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_products_name');
        $this->addSql('CREATE UNIQUE INDEX uniq_products_name_lower ON products (LOWER(name))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_products_name_lower');
        $this->addSql('CREATE UNIQUE INDEX uniq_products_name ON products (name)');
    }
}
