
DROP TABLE IF EXISTS `pw_spider_authors`;

CREATE TABLE `pw_spider_authors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author` varchar(50) NOT NULL DEFAULT '',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0',
  `sp_author` varchar(50) NOT NULL DEFAULT '',
  `state` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `sp_author` (`sp_author`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

