<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410130606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bugs ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bugs ADD CONSTRAINT FK_1E197C912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('CREATE INDEX IDX_1E197C912469DE2 ON bugs (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bugs DROP FOREIGN KEY FK_1E197C912469DE2');
        $this->addSql('DROP INDEX IDX_1E197C912469DE2 ON bugs');
        $this->addSql('ALTER TABLE bugs DROP category_id');
    }
}
