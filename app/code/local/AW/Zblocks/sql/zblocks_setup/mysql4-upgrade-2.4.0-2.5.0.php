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
 * @package    AW_Zblocks
 * @version    2.5.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($this->getTable('zblocks/content'), 'mss_rule_id', "INT(10) NOT NULL DEFAULT '0' COMMENT 'aheadWorks Market Segmentation Suite rule ID'");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/content'), 'store_ids', "VARCHAR(255) NULL");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/content'), 'customer_group', "VARCHAR(255) NULL");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/content'), 'additional_params_serialized', "TEXT NULL");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/zblocks'), 'referer_url', "TEXT");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/zblocks'), 'representation_mode', "INT(10) NULL DEFAULT NULL");
$installer->getConnection()
    ->addColumn($this->getTable('zblocks/zblocks'), 'slider_rotator_interval', "INT(10) NOT NULL DEFAULT 5");
$installer->endSetup();