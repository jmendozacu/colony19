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


class AW_Advancednewsletter_Block_Adminhtml_Subscriber_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'advancednewsletter';
        $this->_controller = 'adminhtml_subscriber';
        parent::__construct();
    }

    public function getHeaderText()
    {
        if ($subscriber = Mage::registry('an_current_subscriber')) {
            if ($subscriber->getEmail()) {
                return Mage::helper('advancednewsletter')->__(
                    "Edit Subscriber '%s'", $this->escapeHtml($subscriber->getEmail())
                );
            } else {
                return Mage::helper('advancednewsletter')->__('Edit Subscriber');
            }
        } else {
            return Mage::helper('advancednewsletter')->__('Add Subscriber');
        }
    }

}