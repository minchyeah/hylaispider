
DROP TABLE IF EXISTS `pw_spider`;

CREATE TABLE `pw_spider` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(15) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `post_time` int(10) unsigned NOT NULL DEFAULT '0',
  `content` text NOT NULL DEFAULT '',
  `spide_time` int(10) unsigned NOT NULL DEFAULT '0',
  `new_author` int(10) unsigned NOT NULL DEFAULT '0',
  `new_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`tid`),
  KEY `author` (`author`),
  KEY `post_time` (`post_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;