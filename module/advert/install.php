<?php
$db = Database::getInstance();


try {
    $db->query("CREATE TABLE IF NOT EXISTS `user_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_slug` varchar(255) NOT NULL,
  `ad_link` text,
  `userid` int(11) NOT NULL,
  `ad_type` varchar(255) DEFAULT NULL,
  `track_id` int(255) DEFAULT NULL,
  `ad_image` varchar(255) DEFAULT NULL,
  `ad_title` varchar(255) DEFAULT NULL,
  `ad_desc` text,
  `ad_placement` int(11) DEFAULT '1',
  `pay_type` varchar(255) DEFAULT NULL,
  `target` text,
  `status` int(11) DEFAULT '1',
  `admin_status` int(11) NOT NULL DEFAULT '0',
  `date_created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;");
} catch (Exception $e){}