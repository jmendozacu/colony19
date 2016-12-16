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
 * @version    2.5.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$installer = $this;
$installer->startSetup();

$installer->getConnection()
    ->addColumn($this->getTable('zblocks/zblocks'), 'is_use_category_filter_custom', 'tinyint not null default 0')
;
$installer->getConnection()
    ->modifyColumn($this->getTable('zblocks/zblocks'), 'category_ids', 'mediumtext not null')
;

$customerGroups = Mage::getResourceModel('customer/group_collection')->load()->getAllIds();
foreach (Mage::getModel('zblocks/zblocks')->getCollection() as $model) {
    $modelCustomerGroups = explode(',' , $model->getCustomerGroup());
    $newCustomerGroups = array_diff($customerGroups, $modelCustomerGroups);
    if (count($newCustomerGroups) === 0) {
        $model->setCustomerGroup(null);
    } else {
        $model->setCustomerGroup(implode(',' , $newCustomerGroups));
    }
    try {
        $model->save();
    } catch (Exception $e) {
        Mage::logException($e);
    }
}

$installer->endSetup();