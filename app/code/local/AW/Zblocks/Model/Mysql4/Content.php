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


class AW_Zblocks_Model_Mysql4_Content extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_serializableFields   = array(
        'additional_params_serialized' => array(null, array())
    );

    public function _construct()
    {    
        $this->_init('zblocks/content', 'block_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!is_null($object->getStoreIds()) && is_array($object->getStoreIds())) {
            $object->setStoreIds(implode(',', $object->getStoreIds()));
        }
        if (!is_null($object->getCustomerGroup()) && is_array($object->getCustomerGroup())) {
            $object->setCustomerGroup(implode(',', $object->getCustomerGroup()));
        }
        $this->_prepareParentParams($object);

        return parent::_beforeSave($object);
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $object->addData($object->getAdditionalParamsSerialized());
        $this->_prepareParentParams($object);

        return parent::_afterLoad($object);
    }

    protected function _prepareParentParams($object)
    {
        $additionalParamsData = array_intersect_key(
            $object->getData(),
            array(
                'use_parent_store_ids'      => null,
                'use_parent_customer_group' => null,
                'use_parent_mss'            => null
            )
        );

        $zblockId = $object->getZblockId();
        $zblockModel = Mage::getModel('zblocks/zblocks')->load($zblockId);
        if ($object->getUseParentStoreIds() || !$additionalParamsData) {
            $object->setStoreIds($zblockModel->getStoreIds());
        }
        if ($object->getUseParentCustomerGroup() || !$additionalParamsData) {
            $object->setCustomerGroup($zblockModel->getCustomerGroup());
        }
        if ($object->getUseParentMss() || !$additionalParamsData) {
            $object->setMssRuleId($zblockModel->getMssRuleId());
        }
        $object->setAdditionalParamsSerialized(serialize($additionalParamsData));
    }
}