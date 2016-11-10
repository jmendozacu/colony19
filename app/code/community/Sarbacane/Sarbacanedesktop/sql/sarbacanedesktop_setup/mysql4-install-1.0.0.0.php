<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Sarbacane
 * @package     Sarbacane_Sarbacanedesktop
 * @author      Sarbacane Software <contact@sarbacane.com>
 * @copyright   2015 Sarbacane Software
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('sarbacanedesktop')}`;
DROP TABLE IF EXISTS `{$this->getTable('sarbacanedesktop_users')}`;

CREATE TABLE `{$this->getTable('sarbacanedesktop')}` (
	`email` varchar(150) NOT NULL,
	`list_type` varchar(20) NOT NULL,
	`store_id` varchar(20) NOT NULL,
	`sd_id` varchar(20) NOT NULL,
	`lastname` varchar(150) NOT NULL,
	`firstname` varchar(150) NOT NULL,
	`orders_data` varchar(150) NOT NULL,
	PRIMARY KEY(`email`,`store_id`,`list_type`,`sd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `{$this->getTable('sarbacanedesktop_users')}` (
	`sd_id` int(20) unsigned NOT NULL AUTO_INCREMENT,
	`sd_type` varchar(20) NOT NULL,
	`sd_value` varchar(200) NOT NULL,
	PRIMARY KEY(`sd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `{$this->getTable('sarbacanedesktop_users')}` (`sd_type`, `sd_value`) VALUES
('sd_token', ''),
('sd_list', '');

");
$installer->endSetup();