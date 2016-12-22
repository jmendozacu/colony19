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


class AW_Advancednewsletter_Model_Mysql4_Segment extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('advancednewsletter/segment', 'segment_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (is_array($object->getData('display_in_store')))
            $object->setData('display_in_store', implode(',', $object->getData('display_in_store')));
        if (is_array($object->getData('display_in_category')))
            $object->setData('display_in_category', implode(',', $object->getData('display_in_category')));
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('display_in_store'))
            $object->setData('display_in_store', explode(',', $object->getData('display_in_store')));
        if ($object->getData('display_in_category'))
            $object->setData('display_in_category', explode(',', $object->getData('display_in_category')));
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if (!is_null($object->getData('display_in_store'))) {
            if (is_string($object->getData('display_in_store'))) {
                $object->setData('display_in_store', explode(',', $object->getData('display_in_store')));
            }
        } else {
            $object->setData('display_in_store', array());
        }
        
        if (!is_null($object->getData('display_in_category'))) {
            if (is_string($object->getData('display_in_category'))) {
                $object->setData('display_in_category', explode(',', $object->getData('display_in_category')));
            }
        } else {
            $object->setData('display_in_category', array());
        }
    }
}