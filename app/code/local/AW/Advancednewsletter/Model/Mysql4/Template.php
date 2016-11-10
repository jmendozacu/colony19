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
 * @version    2.4.7
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancednewsletter_Model_Mysql4_Template extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('advancednewsletter/template', 'template_id');
    }

    /**
     * Load an object by template code
     *
     * @param Mage_Newsletter_Model_Template $object
     * @param string $templateCode
     * @return Mage_Newsletter_Model_Mysql4_Template
     */
    public function loadByCode(Mage_Newsletter_Model_Template $object, $templateCode)
    {
        $read = $this->_getReadAdapter();
        if ($read && !is_null($templateCode)) {
            $select = $this->_getLoadSelect('template_code', $templateCode, $object)
                ->where('template_actual=?', 1)
            ;
            $data = $read->fetchRow($select);
            if ($data) {
                $object->setData($data);
            }
        }

        $this->_afterLoad($object);
        return $this;
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (is_array($object->getData('segments_codes'))) {
            $object->setData('segments_codes', implode(',', $object->getData('segments_codes')));
        }
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('segments_codes')) {
            $object->setData('segments_codes', explode(',', $object->getData('segments_codes')));
        }
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if (!is_null($object->getData('segments_codes'))) {
            if (is_string($object->getData('segments_codes'))) {
                $object->setData('segments_codes', explode(',', $object->getData('segments_codes')));
            }
        } else {
            $object->setData('segments_codes', array());
        }
    }
}