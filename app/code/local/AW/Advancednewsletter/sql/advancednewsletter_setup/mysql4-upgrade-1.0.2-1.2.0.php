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

DROP TABLE IF EXISTS {$this->getTable('advancednewsletter/automanagement')};
CREATE TABLE IF NOT EXISTS {$this->getTable('advancednewsletter/automanagement')} (
  `rule_id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(100) NOT NULL,
  `status` int(11) NOT NULL,
  `conditions_serialized` mediumtext NOT NULL,
  `segments_cut` mediumtext NOT NULL,
  `segments_paste` mediumtext NOT NULL,
  PRIMARY KEY  (`rule_id`)
)  ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE {$this->getTable('advancednewsletter/subscriptions')}
ADD `phone` varchar(100) default NULL,
ADD `salutation` varchar(100) default NULL;

ALTER TABLE {$this->getTable('advancednewsletter/segmentsmanagment')}
ADD UNIQUE (`code`);

");

$installer->endSetup();