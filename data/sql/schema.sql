-- MySQL dump 10.13  Distrib 5.7.16, for Linux (x86_64)
--
-- Host: localhost    Database: pubDBdev
-- ------------------------------------------------------
-- Server version	5.7.16-0ubuntu0.16.10.1

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
-- Table structure for table `additional_info`
--

DROP TABLE IF EXISTS `additional_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `additional_info` (
  `add_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT '',
  `location` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`add_id`)
) ENGINE=MyISAM AUTO_INCREMENT=194 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aicml_positions`
--

DROP TABLE IF EXISTS `aicml_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aicml_positions` (
  `pos_id` int(11) NOT NULL DEFAULT '0',
  `description` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`pos_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aicml_staff`
--

DROP TABLE IF EXISTS `aicml_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aicml_staff` (
  `staff_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL DEFAULT '0',
  `pos_id` int(11) NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`staff_id`)
) ENGINE=MyISAM AUTO_INCREMENT=112 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `attachment_types`
--

DROP TABLE IF EXISTS `attachment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachment_types` (
  `type` varchar(20) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author` (
  `title` varchar(255) DEFAULT '',
  `webpage` varchar(255) DEFAULT '',
  `author_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1035 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `author_interest`
--

DROP TABLE IF EXISTS `author_interest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author_interest` (
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `interest_id` int(10) unsigned NOT NULL DEFAULT '0',
  KEY `author_interest_author_id` (`author_id`),
  KEY `author_interest_interest_id` (`interest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_info`
--

DROP TABLE IF EXISTS `cat_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_info` (
  `cat_id` int(10) unsigned NOT NULL DEFAULT '0',
  `info_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`,`info_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cat_vopts`
--

DROP TABLE IF EXISTS `cat_vopts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cat_vopts` (
  `cat_id` int(11) NOT NULL DEFAULT '0',
  `vopt_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`cat_id`)
) ENGINE=MyISAM AUTO_INCREMENT=69 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `collaboration`
--

DROP TABLE IF EXISTS `collaboration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collaboration` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `extra_info`
--

DROP TABLE IF EXISTS `extra_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `extra_info` (
  `name` varchar(50) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `help_fields`
--

DROP TABLE IF EXISTS `help_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_fields` (
  `name` varchar(100) NOT NULL DEFAULT '',
  `content` blob
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `info`
--

DROP TABLE IF EXISTS `info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `info` (
  `info_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`info_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `interest`
--

DROP TABLE IF EXISTS `interest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interest` (
  `interest_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `interest` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`interest_id`)
) ENGINE=MyISAM AUTO_INCREMENT=51 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pointer`
--

DROP TABLE IF EXISTS `pointer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pointer` (
  `pub_id` int(11) NOT NULL DEFAULT '0',
  `type` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `value` varchar(100) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_add`
--

DROP TABLE IF EXISTS `pub_add`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_add` (
  `pub_id` int(10) unsigned NOT NULL DEFAULT '0',
  `add_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pub_id`,`add_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_author`
--

DROP TABLE IF EXISTS `pub_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_author` (
  `rank` int(11) DEFAULT '0',
  `pub_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pub_id`,`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_cat`
--

DROP TABLE IF EXISTS `pub_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_cat` (
  `pub_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cat_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pub_id`,`cat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_cat_info`
--

DROP TABLE IF EXISTS `pub_cat_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_cat_info` (
  `pub_id` int(10) unsigned NOT NULL DEFAULT '0',
  `cat_id` int(10) unsigned NOT NULL DEFAULT '0',
  `info_id` int(10) unsigned NOT NULL DEFAULT '0',
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pub_id`,`cat_id`,`info_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_col`
--

DROP TABLE IF EXISTS `pub_col`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_col` (
  `pub_id` int(11) NOT NULL DEFAULT '0',
  `col_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_pending`
--

DROP TABLE IF EXISTS `pub_pending`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_pending` (
  `pub_id` int(11) NOT NULL DEFAULT '0',
  `login` varchar(100) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pub_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_rankings`
--

DROP TABLE IF EXISTS `pub_rankings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_rankings` (
  `rank_id` int(11) NOT NULL AUTO_INCREMENT,
  `pub_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`rank_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pub_valid`
--

DROP TABLE IF EXISTS `pub_valid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pub_valid` (
  `pub_id` int(11) NOT NULL DEFAULT '0',
  `login` varchar(100) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pub_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `publication`
--

DROP TABLE IF EXISTS `publication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `publication` (
  `pub_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `paper` varchar(255) NOT NULL DEFAULT '',
  `abstract` blob,
  `keywords` varchar(255) DEFAULT NULL,
  `published` date DEFAULT NULL,
  `venue_id` int(11) DEFAULT '0',
  `extra_info` blob,
  `submit` varchar(100) DEFAULT '',
  `rank_id` int(11) DEFAULT '0',
  `updated` date DEFAULT NULL,
  `user` text,
  PRIMARY KEY (`pub_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1155 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schema_version`
--

DROP TABLE IF EXISTS `schema_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `description` varchar(200) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `script` varchar(1000) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `installed_by` varchar(30) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `installed_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag_ml_history`
--

DROP TABLE IF EXISTS `tag_ml_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_ml_history` (
  `login` varchar(100) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `pub_ids` varchar(255) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `search` varchar(100) DEFAULT '',
  `comments` varchar(100) DEFAULT '',
  `options` int(11) NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `access_level` int(11) NOT NULL DEFAULT '0',
  `email` varchar(100) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(40) NOT NULL DEFAULT '',
  `login` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_author`
--

DROP TABLE IF EXISTS `user_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_author` (
  `login` varchar(100) NOT NULL DEFAULT '',
  `author_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`login`,`author_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `venue`
--

DROP TABLE IF EXISTS `venue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venue` (
  `venue_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT '',
  `name` varchar(100) DEFAULT '',
  `url` varchar(100) DEFAULT '',
  `cat_id` int(11) DEFAULT '0',
  `editor` varchar(100) DEFAULT '',
  `v_usage` tinyint(1) NOT NULL DEFAULT '0',
  `rank_id` int(11) NOT NULL DEFAULT '0',
  `date` date DEFAULT '0000-00-00',
  PRIMARY KEY (`venue_id`)
) ENGINE=MyISAM AUTO_INCREMENT=421 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `venue_occur`
--

DROP TABLE IF EXISTS `venue_occur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venue_occur` (
  `venue_occur_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) NOT NULL DEFAULT '0',
  `location` varchar(100) DEFAULT '',
  `date` date NOT NULL DEFAULT '0000-00-00',
  `url` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`venue_occur_id`),
  KEY `venue_id` (`venue_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1514 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `venue_rankings`
--

DROP TABLE IF EXISTS `venue_rankings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venue_rankings` (
  `rank_id` int(11) NOT NULL AUTO_INCREMENT,
  `venue_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`rank_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `venue_vopts`
--

DROP TABLE IF EXISTS `venue_vopts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venue_vopts` (
  `venue_id` int(11) NOT NULL DEFAULT '0',
  `value` varchar(100) NOT NULL DEFAULT '',
  `vopt_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vopts`
--

DROP TABLE IF EXISTS `vopts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vopts` (
  `vopt_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`vopt_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-12-07 17:07:58
