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
 * @package    AW_Zblocks
 * @version    2.5.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('zblocks_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Block Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('general', array(
            'label' => $this->__('General Information'),
            'title' => $this->__('General Information'),
            'content' => $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_general')->toHtml(),
        ));

        $this->addTab('audience', array(
                'label' => $this->__('Audience'),
                'title' => $this->__('Audience'),
                'content' => $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_audience')->toHtml(),
        ));

        $this->addTab('content', array(
            'label' => $this->__('Content'),
            'title' => $this->__('Content'),
            'content' => $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_content')->toHtml(),
        ));

        $this->addTab('schedule', array(
            'label' => $this->__('Schedule'),
            'title' => $this->__('Schedule'),
            'content' => $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_schedule')->toHtml(),
        ));

        $this->addTab('conditions', array(
                'label' => $this->__('Conditions'),
                'title' => $this->__('Conditions'),
                'content' => $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_conditions')->toHtml(),
        ));

        $this->_updateActiveTab();

        return parent::_beforeToHtml();
    }

    protected function _updateActiveTab()
    {
        $tabId = $this->getRequest()->getParam('tab');
        if ($tabId) {
            $tabId = preg_replace("#{$this->getId()}_#", '', $tabId);
            if ($tabId) {
                $this->setActiveTab($tabId);
            }
        }
    }
}
