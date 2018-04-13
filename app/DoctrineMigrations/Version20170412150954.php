<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170412150954 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX last_price_idx ON trade');
        $this->addSql('CREATE INDEX last_price_time_idx ON trade (symbol_id, time)');
        $this->addSql('CREATE INDEX last_price_idx ON trade (symbol_id, created_at)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX last_price_time_idx ON trade');
        $this->addSql('DROP INDEX last_price_idx ON trade');
        $this->addSql('CREATE INDEX last_price_idx ON trade (symbol_id, time)');
    }
}
