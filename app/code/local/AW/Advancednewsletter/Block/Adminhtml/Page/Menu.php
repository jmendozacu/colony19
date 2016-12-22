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


class AW_Advancednewsletter_Block_Adminhtml_Page_Menu extends Mage_Adminhtml_Block_Page_Menu
{
    /**
     * Check is module output enabled
     *
     * @param Varien_Simplexml_Element $child
     * @return bool
     */
    protected function _isEnabledModuleOutput(Varien_Simplexml_Element $child)
    {
        $helperName = 'adminhtml';
        $childAttributes = $child->attributes();
        if (isset($childAttributes['module']) && $childAttributes['module'] != 'newsletter') {
            $helperName = (string)$childAttributes['module'];
        }

        return Mage::helper($helperName)->isModuleOutputEnabled();
    }
}