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

$installer->run("DROP TABLE IF EXISTS `{$this->getTable('sarbacanedesktop')}`;");
$installer->run("DROP TABLE IF EXISTS `{$this->getTable('sd_updates')}`;");

$installer->run("ALTER TABLE `{$this->getTable('sarbacanedesktop_users')}` ADD COLUMN `list_id` VARCHAR(50) NULL DEFAULT NULL AFTER `sd_value`;");
$installer->run("ALTER TABLE `{$this->getTable('sarbacanedesktop_users')}` ADD COLUMN `last_call_date` VARCHAR(50) NULL DEFAULT NULL AFTER `list_id`;");

$installer->run("
CREATE TABLE `{$this->getTable('sd_updates')}` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(50) NOT NULL COLLATE 'utf8_bin',
	`update_time` DATETIME NOT NULL,
	`action` VARCHAR(5) NOT NULL COLLATE 'utf8_bin',
	`list_type` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_bin',
	PRIMARY KEY (`id`),
	INDEX `update_time_action` (`update_time`, `action`)
)
COMMENT='Contains useful data used by SD/MF plugin'
COLLATE='utf8_bin'
ENGINE=InnoDB
;");

$installer->run("DROP TRIGGER IF EXISTS sd_newsletter_update;");
$installer->run("DROP TRIGGER IF EXISTS sd_customer_delete;");

$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$sql= "
CREATE TRIGGER `sd_newsletter_update` 
AFTER UPDATE ON `{$this->getTable('newsletter_subscriber')}` 
FOR EACH ROW BEGIN 
	DELETE FROM {$this->getTable('sd_updates')} WHERE email=NEW.subscriber_email AND list_type='N'; 
	IF NEW.subscriber_status = 1 THEN 
		INSERT INTO {$this->getTable('sd_updates')} (email,update_time,action,list_type) VALUES (NEW.subscriber_email,UTC_TIMESTAMP(),'S','N'); 
	ELSE 
		INSERT INTO {$this->getTable('sd_updates')} (email,update_time,action,list_type) VALUES (NEW.subscriber_email,UTC_TIMESTAMP(),'U','N'); 
	END IF; 
END;";
$write->exec($sql);

$sql= "
CREATE TRIGGER `sd_newsletter_insert` 
AFTER INSERT ON `{$this->getTable('newsletter_subscriber')}` 
FOR EACH ROW BEGIN 
	DELETE FROM {$this->getTable('sd_updates')} WHERE email=NEW.subscriber_email AND list_type='N'; 
	IF NEW.subscriber_status = 1 THEN 
		INSERT INTO {$this->getTable('sd_updates')} (email,update_time,action,list_type) VALUES (NEW.subscriber_email,UTC_TIMESTAMP(),'S','N'); 
	ELSE 
		INSERT INTO {$this->getTable('sd_updates')} (email,update_time,action,list_type) VALUES (NEW.subscriber_email,UTC_TIMESTAMP(),'U','N'); 
	END IF; 
END;";
$write->exec($sql);

$sql = "
CREATE TRIGGER `sd_customer_delete` 
BEFORE DELETE ON `{$this->getTable('customer_entity')}` 
FOR EACH ROW BEGIN 
	DELETE FROM {$this->getTable('sd_updates')} WHERE email=OLD.email AND list_type='C'; 
	INSERT INTO {$this->getTable('sd_updates')} (email,update_time,action,list_type) VALUES (OLD.email,UTC_TIMESTAMP(),'U','C'); 
END";
$write->exec($sql);


$installer->endSetup();