SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `actor_data`
-- ----------------------------
DROP TABLE IF EXISTS `actor_data`;
CREATE TABLE `actor_data` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `age` tinyint(4) DEFAULT NULL,
  `movie` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_ACTOR` (`actor_id`),
  CONSTRAINT `FK_ACTOR` FOREIGN KEY (`actor_id`) REFERENCES `actor_entity` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for `actor_entity`
-- ----------------------------
DROP TABLE IF EXISTS `actor_entity`;
CREATE TABLE `actor_entity` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uin` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_UIN` (`uin`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=388 DEFAULT CHARSET=utf8;
