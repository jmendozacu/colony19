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
 * @version    2.5.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit_Tab_Content_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('zblocks_content_edit_form'); 
        $this->setTitle($this->__('Block Information'));
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        try {
            if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
                $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
            }
        } catch (Exception $ex) {

        }
    }

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'      => 'edit_form',
            'action'  => $this->getUrl('*/*/saveContent', array('id' => $this->getRequest()->getParam('id'))),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setHtmlIdPrefix('page_');

        $this->_addContentFieldsetToForm($form);
        $this->_addAdditionalFieldsetToForm($form);

        if($data = Mage::registry('zblocks_content')) {
            $form->addValues($data);
        }
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _addContentFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_content', array(
                'legend' => $this->__('Content Item Information'),
                'class'  => 'fieldset-wide'
        ));

        $fieldset->addField('zblock_id', 'hidden', array(
                'name'      => 'zblock_id',
        ));

        $fieldset->addField('title', 'text', array(
                'name'      => 'title',
                'label'     => $this->__('Title'),
                'title'     => $this->__('Title'),
        ));

        $fieldset->addField('is_active', 'select', array(
                'label'     => $this->__('Status'),
                'title'     => $this->__('Status'),
                'name'      => 'is_active',
                'options'   => array(
                    1 => $this->__('Enabled'),
                    0 => $this->__('Disabled'),
                ),
        ));

        $fieldset->addField('sort_order', 'text', array(
                'name'      => 'sort_order',
                'label'     => $this->__('Sort Order'),
                'style'     => 'width:274px !important;',
        ));

        try{
            $config = Mage::getSingleton('cms/wysiwyg_config')->getConfig();
            $config->setData(Mage::helper('zblocks')->recursiveReplace(
                    '/zblocks_admin/',
                    '/'.(string)Mage::app()->getConfig()->getNode('admin/routers/adminhtml/args/frontName').'/',
                    $config->getData()
                )
            );
        }
        catch (Exception $ex){
            $config = null;
        }

        $fieldset->addField('content', 'editor', array(
                'name'      => 'content',
                'label'     => $this->__('Content'),
                'title'     => $this->__('Content'),
                'style'     => 'height:36em',
                'required'  => true,
                'config'    => $config
        ));

        return $this;
    }

    protected function _addAdditionalFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_additional_settings', array(
                'legend' => $this->__('Additional Settings'),
                'class'  => 'fieldset-wide'
        ));

        if (Mage::app()->isSingleStoreMode()) {
            /* get default store view */
            $defaultStoreView = Mage::app()->getDefaultStoreView();

            if (!is_null($defaultStoreView)) {
                $storeView = $defaultStoreView->getId();
            } else {
                /* Default store view is somehow deleted */
                $storeView = Mage::app()->getStore()->getId();
            }

            $fieldset->addField('store_ids', 'hidden', array(
                    'name'  => 'store_ids[]',
                    'value' => $storeView
            ));
        } else {
            $fieldset->addField('store_ids', 'multiselect', array(
                    'name'               => 'store_ids[]',
                    'label'              => $this->__('Store View'),
                    'title'              => $this->__('Store View'),
                    'required'           => true,
                    'values'             => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                    'after_element_html' => $this->_getDefaultCheckbox('use_parent_store_ids_value', 'use_parent_store_ids')
            ));
        }

        $fieldset->addField('customer_group', 'multiselect', array(
                'name'      => 'customer_group[]',
                'label'     => $this->__('Enable z-block for certain customer groups'),
                'title'     => $this->__('Enable z-block for certain customer groups'),
                'required'  => false,
                'values'    => Mage::getResourceModel('customer/group_collection')->load()->toOptionArray(),
                'after_element_html' => $this->_getDefaultCheckbox('use_parent_customer_group_value', 'use_parent_customer_group')
        ));

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
                    'label' => $this->__('Validate the block by MSS rule'),
                    'name' => 'mss_rule_id',
                    'values' => $mssRules,
                    'note' => $this->__('Only active MSS rules are listed here'),
                    'after_element_html' => $this->_getDefaultCheckbox('use_parent_mss_value', 'use_parent_mss')
            ));
        } else {
            $fieldset->addField('mss_rule_id', 'hidden', array(
                    'name' => 'mss_rule_id',
            ));

            $fieldset->addField('mss_warning', 'note', array(
                    'label' => $this->__('Validate the block by MSS rule'),
                    'text' => $this->__("MSS is not installed")
            ));
        }
    }

    protected function _getDefaultCheckbox($fieldId, $fieldName)
    {
        $data = Mage::registry('zblocks_content');

        $afterElementHtml = '<div><input type="checkbox" id="' . $fieldId . '" '
            . 'name="' . $fieldName . '" value="1"'
            . 'onclick="toggleValueElements(this, this.parentNode.parentNode);">'
            . '<label for="' . $fieldId . '" class="normal">'
            .  $this->__('Use parent values')
            . '</label></div>'
        ;
        if ((isset($data[$fieldName]) && $data[$fieldName])
            || (!isset($data['block_id'])|| null === $data['block_id'])
        ) {
            $afterElementHtml .= '<script type="text/javascript">'
                . 'Event.observe(window, "load", function(){'
                . '$("' . $fieldId . '").click();'
                . '});'
                . '</script>';
        }
        return $afterElementHtml;
    }
}