CREATE TABLE `schema_version` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(50) COLLATE latin1_general_cs NOT NULL,
  `description` varchar(200) COLLATE latin1_general_cs NOT NULL,
  `script` varchar(1000) COLLATE latin1_general_cs NOT NULL,
  `installed_by` varchar(30) COLLATE latin1_general_cs NOT NULL,
  `installed_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO schema_version (version,description,script,installed_by,installed_on)
       VALUES ('1.1.0','booktitle fix','upgrade_v100_v110.sql',user(),now());

UPDATE info SET name='Booktitle' WHERE info_id=3;

