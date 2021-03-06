
DROP TABLE IF EXISTS `pw_spider`;

CREATE TABLE `pw_spider` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `author` varchar(25) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `post_time` int(10) unsigned NOT NULL DEFAULT '0',
  `content` text,
  `spide_time` int(10) unsigned NOT NULL DEFAULT '0',
  `state` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `new_tid` int(10) unsigned NOT NULL DEFAULT '0',
  `new_author` varchar(25) NOT NULL DEFAULT '',
  `new_post_time` int(10) unsigned NOT NULL DEFAULT '0',
  `new_url` varchar(255) NOT NULL DEFAULT '',
  `new_state` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tid` (`tid`),
  KEY `new_tid` (`new_tid`),
  KEY `author` (`author`),
  KEY `post_time` (`post_time`)
)DEFAULT CHARSET=utf8;

