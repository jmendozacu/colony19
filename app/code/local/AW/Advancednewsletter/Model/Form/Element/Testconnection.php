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


class AW_Advancednewsletter_Model_Form_Element_Testconnection extends Varien_Data_Form_Element_Text
{
    /**
     * Status connection successed
     */
    const STATUS_SUCCESS = '1';

    /**
     * Status connection failed
     */
    const STATUS_FAIL = '0';

    /**
     * Retrives html block
     * @return string
     */
    public function getBlockHtml()
    {
        $block = Mage::app()->getLayout()->createBlock('advancednewsletter/adminhtml_form_element_testconnection');
        if ($block) {
            return $block->toHtml();
        }
        return '';
    }

    /**
     * Retrives element html
     * @return string
     */
    public function getElementHtml()
    {
        return "<div id=\"test_connection_container\">" . $this->getBlockHtml() . "</div>";
    }
}