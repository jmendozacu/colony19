<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancednewsletter
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/segmentsmanagment')} (
  `segment_id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `default_store` smallint(5) NOT NULL,
  `default_category` varchar(255) NOT NULL,
  `display_in_store` varchar(255) NOT NULL,
  `display_in_category` varchar(255) NOT NULL,
  `display_order` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/subscriptions')} (
  `id` int(11) NOT NULL auto_increment,
  `segments_codes` text NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/templates')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `segments_ids` text NOT NULL,
  `template_id` int(11) NOT NULL,
  `smtp_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `template_id` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/smtpconfiguration')} (
  `smtp_id` int(11) unsigned NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `server_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `port` int(11) unsigned NOT NULL,
  `usessl` tinyint(1) NOT NULL,
  PRIMARY KEY  (`smtp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

$installer->endSetup(); 