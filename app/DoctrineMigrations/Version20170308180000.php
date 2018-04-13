<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170308180000 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;
    /** @var AbstractSchemaManager */
    private $schemaManager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->schemaManager = $container->get('doctrine')->getConnection()->getSchemaManager();
    }
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        if ($this->schemaManager->tablesExist('network')) {
            return;
        }
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql("-- MySQL dump 10.13  Distrib 5.7.17, for Linux (x86_64)
--
-- Host: nc_db    Database: neuralcoin
-- ------------------------------------------------------
-- Server version	5.7.17

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `network`
--

DROP TABLE IF EXISTS `network`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `network` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `time_scope` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `normalize_inputs` tinyint(1) NOT NULL,
  `interpolate_inputs` tinyint(1) NOT NULL,
  `interpolation_interval` int(11) DEFAULT NULL,
  `input_steps` int(11) NOT NULL DEFAULT '1',
  `activation_function` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hidden_layers` int(11) NOT NULL,
  `file_path` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mean_error` double DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `network`
--

LOCK TABLES `network` WRITE;
/*!40000 ALTER TABLE `network` DISABLE KEYS */;
/*!40000 ALTER TABLE `network` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `output_config`
--

DROP TABLE IF EXISTS `output_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `output_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price_prediction_symbol_id` int(11) DEFAULT NULL,
  `network_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `type` smallint(6) NOT NULL,
  `absolute_values` tinyint(1) NOT NULL,
  `steps_ahead` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_57159534128B91` (`network_id`),
  KEY `IDX_571595B65EB500` (`price_prediction_symbol_id`),
  CONSTRAINT `FK_57159534128B91` FOREIGN KEY (`network_id`) REFERENCES `network` (`id`),
  CONSTRAINT `FK_571595B65EB500` FOREIGN KEY (`price_prediction_symbol_id`) REFERENCES `symbol` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `output_config`
--

LOCK TABLES `output_config` WRITE;
/*!40000 ALTER TABLE `output_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `output_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `symbol`
--

DROP TABLE IF EXISTS `symbol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `symbol` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `trades_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `symbol`
--

LOCK TABLES `symbol` WRITE;
/*!40000 ALTER TABLE `symbol` DISABLE KEYS */;
INSERT INTO `symbol` VALUES (1,'ETHREP',1);
/*!40000 ALTER TABLE `symbol` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `symbols_network`
--

DROP TABLE IF EXISTS `symbols_network`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `symbols_network` (
  `network_id` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `symbol_id` int(11) NOT NULL,
  PRIMARY KEY (`network_id`,`symbol_id`),
  KEY `IDX_B94A773834128B91` (`network_id`),
  KEY `IDX_B94A7738C0F75674` (`symbol_id`),
  CONSTRAINT `FK_B94A773834128B91` FOREIGN KEY (`network_id`) REFERENCES `network` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B94A7738C0F75674` FOREIGN KEY (`symbol_id`) REFERENCES `symbol` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `symbols_network`
--

LOCK TABLES `symbols_network` WRITE;
/*!40000 ALTER TABLE `symbols_network` DISABLE KEYS */;
/*!40000 ALTER TABLE `symbols_network` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trade`
--

DROP TABLE IF EXISTS `trade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `symbol_id` int(11) DEFAULT NULL,
  `exchange_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `price` decimal(16,8) NOT NULL,
  `create_at` datetime NOT NULL,
  `volume` double NOT NULL,
  `time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_trade` (`exchange_name`,`symbol_id`,`time`),
  KEY `IDX_7E1A4366C0F75674` (`symbol_id`),
  CONSTRAINT `FK_7E1A4366C0F75674` FOREIGN KEY (`symbol_id`) REFERENCES `symbol` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trade`
--

LOCK TABLES `trade` WRITE;
/*!40000 ALTER TABLE `trade` DISABLE KEYS */;
INSERT INTO `trade` VALUES (1,1,'poloniex',0.32211890,'2017-03-09 15:33:13',208.45362238,'2017-03-09 15:33:13');
/*!40000 ALTER TABLE `trade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `training_run`
--

DROP TABLE IF EXISTS `training_run`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `training_run` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:guid)',
  `network_id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '(DC2Type:guid)',
  `traning_data_file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CAC8D38934128B91` (`network_id`),
  CONSTRAINT `FK_CAC8D38934128B91` FOREIGN KEY (`network_id`) REFERENCES `network` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `training_run`
--

LOCK TABLES `training_run` WRITE;
/*!40000 ALTER TABLE `training_run` DISABLE KEYS */;
/*!40000 ALTER TABLE `training_run` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-03-09 16:33:39

        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
    }
}
