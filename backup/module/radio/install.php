<?php
$db = Database::getInstance();

try {
    $db->query("CREATE TABLE IF NOT EXISTS `radios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text,
  `slug` varchar(255) DEFAULT NULL,
  `link` text,
  `link_type` varchar(255) NOT NULL,
  `art` varchar(255) DEFAULT NULL,
  `genre` int(11) DEFAULT NULL,
  `tags` text,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;");
} catch (Exception $e){}

try {
    $db->query("CREATE TABLE IF NOT EXISTS `radio_listeners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `userip` varchar(255) DEFAULT NULL,
  `radio_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;");
} catch (Exception $e){}