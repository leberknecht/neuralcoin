<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170923173931 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE network ADD custom_shape TINYINT(1) DEFAULT \'0\' NOT NULL, ADD shape LONGTEXT NOT NULL COMMENT \'(DC2Type:json_array)\', CHANGE separate_input_symbols separate_input_symbols TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE boost_hits_in_training_data boost_hits_in_training_data INT DEFAULT 0 NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE network DROP custom_shape, DROP shape, CHANGE boost_hits_in_training_data boost_hits_in_training_data VARCHAR(255) DEFAULT \'0\' NOT NULL COLLATE utf8_unicode_ci, CHANGE separate_input_symbols separate_input_symbols VARCHAR(255) DEFAULT \'0\' NOT NULL COLLATE utf8_unicode_ci');
    }
}
