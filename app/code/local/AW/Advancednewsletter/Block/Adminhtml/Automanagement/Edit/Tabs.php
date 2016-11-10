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


class AW_Advancednewsletter_Block_Adminhtml_Automanagement_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('rule_id');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('advancednewsletter')->__('Auto-management rules'));
    }

    protected function _beforeToHtml()
    {
        $mainSectionContent = $this->getLayout()
            ->createBlock('advancednewsletter/adminhtml_automanagement_edit_tab_main')
            ->toHtml()
        ;
        $this->addTab(
            'main_section',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Rule Information'),
                'title' => Mage::helper('advancednewsletter')->__('Rule Information'),
                'content' => $mainSectionContent,
                'active' => true
            )
        );

        $conditionsSectionContent = $this->getLayout()
            ->createBlock('advancednewsletter/adminhtml_automanagement_edit_tab_conditions')
            ->toHtml()
        ;
        $this->addTab(
            'conditions_section',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Conditions'),
                'title' => Mage::helper('advancednewsletter')->__('Conditions'),
                'content' => $conditionsSectionContent,
            )
        );

        $actionsSectionContent = $this->getLayout()
            ->createBlock('advancednewsletter/adminhtml_automanagement_edit_tab_actions')
            ->toHtml()
        ;
        $this->addTab(
            'actions_section',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Actions'),
                'title' => Mage::helper('advancednewsletter')->__('Actions'),
                'content' => $actionsSectionContent,
            )
        );
        return parent::_beforeToHtml();
    }

}
