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


class AW_Advancednewsletter_Model_Source_Formpositions
{
    const IN_BLOCK = 'in_block';
    const AJAX_LAYER = 'ajax_layer';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::IN_BLOCK,
                'label' => Mage::helper('advancednewsletter')->__('In block')
            ),
            array(
                'value' => self::AJAX_LAYER,
                'label' => Mage::helper('advancednewsletter')->__('AJAX layer')
            ),
        );
    }

}