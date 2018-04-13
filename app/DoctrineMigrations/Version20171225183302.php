<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171225183302 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE order_book (id INT AUTO_INCREMENT NOT NULL, symbol_id INT DEFAULT NULL, created_at DATETIME NOT NULL, exchange VARCHAR(255) NOT NULL, buy LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', sell LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', date DATETIME NOT NULL, INDEX IDX_86149926C0F75674 (symbol_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_book ADD CONSTRAINT FK_86149926C0F75674 FOREIGN KEY (symbol_id) REFERENCES symbol (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE order_book');
    }
}
