
DROP TABLE IF EXISTS `pw_spider_settings`;

CREATE TABLE `pw_spider_settings` (
  `skey` varchar(100) NOT NULL DEFAULT '',
  `svalue` varchar(1000) NOT NULL DEFAULT '',
  `remark` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`skey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

