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


class AW_Advancednewsletter_Block_Adminhtml_Segment_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('segment_id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $fieldset = $form->addFieldset(
            'main_group', array('legend' => Mage::helper('advancednewsletter')->__('Fields'))
        );

        $fieldset->addField(
            'title', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Segment Title'),
                'name' => 'title',
                'required' => true,
            )
        );

        $noteText = Mage::helper('advancednewsletter')->__(
            "Note that this field's text  is case-sensitive <br /> and must exactly correspond to the MailChimp text"
        );
        $noteText .= '<br />' . Mage::helper('advancednewsletter')->__("This value can not be changed after saving");
        $params = array(
            'label' => Mage::helper('advancednewsletter')->__('Segment Code'),
            'name' => 'code',
            'required' => true,
            'after_element_html' => '<p><small>' . $noteText . '<small></p>',
        );
        
        $isCodeEditDisabled = !!($this->getRequest()->getParam('id'));
        if ($isCodeEditDisabled) {
            $params['readonly'] = 'readonly';
        }
        $fieldset->addField('code', 'text', $params);

        $fieldset->addField(
            'default_store', 'select',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Default Store'),
                'name' => 'default_store',
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'required' => true,
            )
        );

        $fieldset->addField(
            'default_category', 'select',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Default Category'),
                'name' => 'default_category',
                'values' => Mage::helper('advancednewsletter')->getCategoriesArray(),
                'after_element_html' => '',
                'required' => true,
            )
        );

        $fieldset->addField(
            'display_in_store', 'multiselect',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Display in store'),
                'name' => 'display_in_store',
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'after_element_html' => '',
                'required' => true,
            )
        );

        $fieldset->addField(
            'display_in_category', 'multiselect',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Display in category'),
                'name' => 'display_in_category',
                'values' => Mage::helper('advancednewsletter')->getCategoriesArray(),
                'after_element_html' => '',
                'required' => true,
            )
        );

        $fieldset->addField(
            'frontend_visibility', 'select',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Visible at frontend'),
                'name' => 'frontend_visibility',
                'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
                'after_element_html' => '',
                'required' => true,
            )
        );

        $fieldset->addField(
            'checkout_visibility', 'select',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Visible at checkout'),
                'name' => 'checkout_visibility',
                'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
                'onchange' => '',
                'after_element_html' => '',
                'required' => true,
            )
        );

        $fieldset->addField(
            'display_order', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Display order'),
                'name' => 'display_order',
            )
        );

        if (Mage::getSingleton('adminhtml/session')->getAnSegmentData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getAnSegmentData());
            Mage::getSingleton('adminhtml/session')->setAnSegmentData(null);
        } elseif (Mage::registry('an_current_segment')) {
            $form->setValues(Mage::registry('an_current_segment'));
            Mage::unregister('an_current_segment');
        }

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}