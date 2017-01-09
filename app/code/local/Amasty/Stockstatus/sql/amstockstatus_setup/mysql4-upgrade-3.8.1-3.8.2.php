<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE `{$this->getTable('amasty_stockstatus_history')}`
CHANGE COLUMN `order_id` `order_id` VARCHAR(50) DEFAULT NULL,
CHANGE COLUMN `product_id` `product_id` INT(10) UNSIGNED DEFAULT NULL,
ADD KEY `amasty_stockstatus_history` (`order_id`, `product_id`);
");
$installer->endSetup();