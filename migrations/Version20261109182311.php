<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261109182311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the Catalog products table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE products (unit VARCHAR(16) NOT NULL, id UUID NOT NULL, name VARCHAR(100) NOT NULL, price_amount INT NOT NULL, price_currency VARCHAR(3) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_products_name ON products (name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE products');
    }
}
