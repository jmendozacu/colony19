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

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/subscriber')} (
  `id` int(11) NOT NULL auto_increment,
  `store_id` smallint(5),
  `customer_id` int(11),
  `email` varchar(255) NOT NULL,
  `status` int(11),
  `segments_codes` text NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `phone` varchar(100) NOT NULL,
  `salutation` varchar(100) NOT NULL,
  `confirm_code` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('advancednewsletter/segment')} ADD `frontend_visibility` TINYINT NOT NULL AFTER `display_in_category`;

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/template')} (
  `template_id` int(7) unsigned NOT NULL auto_increment,
  `template_code` varchar(150) default NULL,
  `template_text` text,
  `template_text_preprocessed` text,
  `template_styles` text,
  `template_type` int(3) unsigned default NULL,
  `template_subject` varchar(200) default NULL,
  `template_sender_name` varchar(200) default NULL,
  `template_sender_email` varchar(200) default NULL,
  `template_actual` tinyint(1) unsigned default '1',
  `added_at` datetime default NULL,
  `modified_at` datetime default NULL,
  `segments_codes` text NOT NULL,
  `smtp_id` text NOT NULL,
  PRIMARY KEY  (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/queue')} (
  `queue_id` int(7) unsigned NOT NULL auto_increment,
  `template_id` int(7) unsigned NOT NULL default '0',
  `queue_status` int(3) unsigned NOT NULL default '0',
  `queue_start_at` datetime default NULL,
  `queue_finish_at` datetime default NULL,
  PRIMARY KEY  (`queue_id`),
  CONSTRAINT `AN_FK_QUEUE_TEMPLATE` FOREIGN KEY (`template_id`) REFERENCES {$this->getTable('advancednewsletter/template')} (`template_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

  CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/queue_link')} (
  `queue_link_id` int(9) unsigned NOT NULL auto_increment,
  `queue_id` int(7) unsigned NOT NULL default '0',
  `subscriber_id` int(7) unsigned NOT NULL default '0',
  `letter_sent_at` datetime default NULL,
  PRIMARY KEY  (`queue_link_id`),
  CONSTRAINT `AN_FK_QUEUE_LINK_QUEUE` FOREIGN KEY (`queue_id`) REFERENCES {$this->getTable('advancednewsletter/queue')} (`queue_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

  CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/queue_store_link')} (
  `queue_id` int(7) unsigned NOT NULL default '0',
  `store_id` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`queue_id`,`store_id`),
  CONSTRAINT `AN_FK_LINK_QUEUE` FOREIGN KEY (`queue_id`) REFERENCES {$this->getTable('advancednewsletter/queue')} (`queue_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

Mage::getConfig()->saveConfig('advanced/modules_disable_output/Mage_Newsletter', 1);

Mage::helper('advancednewsletter/anVersionExport')->exportStart();

$installer->endSetup(); 