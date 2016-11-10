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


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit_Tab_Audience extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);

        $this
            ->_addGroupFieldsetToForm($form)
            ->_addRefererFieldsetToForm($form)
            ->_addMSSFieldsetToForm($form);

        if ($data = Mage::registry('zblocks_data')) {
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }

    protected function _addMSSFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_mss', array('legend' => $this->__('Market Segmentation Suite')));

        if (Mage::helper('zblocks')->isMSSInstalled()) {
            $ruleCollection = Mage::getModel('marketsuite/api')->getRuleCollection();

            $mssRules = array(
                array(
                    'value' => 0,
                    'label' => '',
                ));

            foreach ($ruleCollection as $rule) {
                if ($rule->getIsActive()) {
                    $mssRules[] = array(
                        'value' => $rule->getId(),
                        'label' => $rule->getName(),
                    );
                }
            }

            $fieldset->addField('mss_rule_id', 'select', array(
                    'label'  => $this->__('Validate the block by MSS rule'),
                    'name'   => 'mss_rule_id',
                    'values' => $mssRules,
                    'note'   => $this->__('Only active MSS rules are listed here'),
            ));
        } else {
            $fieldset->addField('mss_rule_id', 'hidden', array(
                    'name' => 'mss_rule_id',
            ));

            $mssWarningBlock = $this->getLayout()
                ->createBlock('core/template')
                ->setTemplate('aw_zblocks/mss/notice.phtml');

            $fieldset->addField('mss_warning', 'hidden', array(
                    'after_element_html' => $mssWarningBlock->toHtml()
            ));
        }

        return $this;
    }

    protected function _addGroupFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_customer_group', array('legend' => $this->__('Customer Group')));

        $fieldset->addField('customer_group', 'multiselect', array(
                'name'      => 'customer_group[]',
                'label'     => $this->__('Enable z-block for certain customer groups'),
                'title'     => $this->__('Enable z-block for certain customer groups'),
                'required'  => false,
                'values'    => Mage::getResourceModel('customer/group_collection')->load()->toOptionArray()
        ));

        return $this;
    }

    protected function _addRefererFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_referer', array('legend' => $this->__('Referer Page')));

        $fieldset->addField('referer_url', 'text', array(
                'name'  => 'referer_url',
                'label' => $this->__('Referer URL'),
                'title' => $this->__('Referer URL'),
                'note'  => $this->__(
                        "Display this Z-Block only if customer came from this page. You can use full URL or a part of URL, e.g. 'catalogsearch'."
                    ),
        ));

        return $this;
    }
}
