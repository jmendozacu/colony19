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
 * @package    AW_Catalogpermissions
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Catalogpermissions_Model_Product_Attribute_Frontend_MssAttr
    extends Mage_Eav_Model_Entity_Attribute_Frontend_Abstract
{
    protected $_isTranslated = false;

    public function getAttribute()
    {
        if (!$this->_isTranslated) {
            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $note = $this->_helper()->__('This MSS segment, if selected, will replace the customer group selection for the appropriate setting');
            }
            else {
                $note = $this->_helper()->__('This MSS rule adds to selected customer groups as AND condition');
            }
            $this->_attribute->setData('note', $note);
            $this->_isTranslated = true;
        }
        return parent::getAttribute();
    }

    protected function _helper()
    {
        return Mage::helper('catalogpermissions');
    }
}
