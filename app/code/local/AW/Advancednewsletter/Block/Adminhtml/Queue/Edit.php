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


class AW_Advancednewsletter_Block_Adminhtml_Queue_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'advancednewsletter';
        $this->_controller = 'adminhtml_queue';
        parent::__construct();
    }

    protected function _toHtml()
    {
        $this->_removeButton('delete');
        if (
                Mage::registry('current_queue') &&
                Mage::registry('current_queue')->getQueueId() &&
                Mage::registry('current_queue')->getQueueStatus() != Mage_Newsletter_Model_Queue::STATUS_NEVER
        ) {
            $this->_removeButton('save');
            $this->_removeButton('reset');
        }
        return parent::_toHtml();
    }

    protected function _prepareLayout()
    {
        // Load Wysiwyg on demand and Prepare layout
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()
                && ($block = $this->getLayout()->getBlock('head'))) {
                $block->setCanLoadTinyMce(true);
            }
        }
        return parent::_prepareLayout();
    }

    public function getHeaderText()
    {
        if ($queue = Mage::registry('current_queue')) {
            return Mage::helper('advancednewsletter')->__('Edit Queue');
        } else {
            return Mage::helper('advancednewsletter')->__('Add Queue');
        }
    }

    public function getBackUrl()
    {
        return $this->getUrl($this->getRequest()->getParam('template_id') ? '*/awadvancedneswsletter_template/' : '*/*');
    }

}
