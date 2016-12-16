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


class AW_Advancednewsletter_Model_Source_Defaultsubscription
{
    const ALL = 'all';
    const STORE_DEFAULT = 'store_default';
    const CATEGORY_DEFAULT = 'category_default';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::ALL,
                'label' => Mage::helper('advancednewsletter')->__('All')
            ),
            array(
                'value' => self::STORE_DEFAULT,
                'label' => Mage::helper('advancednewsletter')->__('Store default')
            ),
            array(
                'value' => self::CATEGORY_DEFAULT,
                'label' => Mage::helper('advancednewsletter')->__('Category default')
            ),
        );
    }

}