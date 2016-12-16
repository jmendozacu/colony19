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


class AW_Advancednewsletter_Model_Mysql4_Automanagement extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('advancednewsletter/automanagement', 'rule_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (is_array($object->getData('segments_cut'))) {
            $object->setData('segments_cut', implode(',', $object->getData('segments_cut')));
        }
        if (!$object->getData('segments_cut')) {
            $object->setData('segments_cut', '');
        }
        if (is_array($object->getData('segments_paste'))) {
            $object->setData('segments_paste', implode(',', $object->getData('segments_paste')));
        }
        if (!$object->getData('segments_paste')) {
            $object->setData('segments_paste', '');
        }
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('segments_cut')) {
            $object->setData('segments_cut', explode(',', $object->getData('segments_cut')));
        }
        if ($object->getData('segments_paste')) {
            $object->setData('segments_paste', explode(',', $object->getData('segments_paste')));
        }
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('segments_cut')) {
            $object->setData('segments_cut', explode(',', $object->getData('segments_cut')));
        } else {
            $object->setData('segments_cut', array());
        }
        if ($object->getData('segments_paste')) {
            $object->setData('segments_paste', explode(',', $object->getData('segments_paste')));
        } else {
            $object->setData('segments_paste', array());
        }
    }
}