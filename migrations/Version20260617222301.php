<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260617222301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, external_id VARCHAR(100) NOT NULL, source VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, description CLOB DEFAULT NULL, contract_type VARCHAR(50) DEFAULT NULL, is_alternance BOOLEAN NOT NULL, category VARCHAR(50) DEFAULT NULL, url VARCHAR(500) DEFAULT NULL, published_at DATETIME NOT NULL, expires_at DATETIME DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F89F75D7B0 ON job (external_id)');
        $this->addSql('CREATE INDEX idx_external_id ON job (external_id)');
        $this->addSql('CREATE INDEX idx_status ON job (status)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE job');
    }
}
