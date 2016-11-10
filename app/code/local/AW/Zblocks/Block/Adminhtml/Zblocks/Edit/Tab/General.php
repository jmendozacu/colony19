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


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $this->setChild('form_after', $this->getLayout()
                ->createBlock('adminhtml/widget_form_element_dependence')
        );
        $this
            ->_addGeneralFieldsetToForm($form)
            ->_addPositionFieldsetToForm($form)
            ->_addRotationFieldsetToForm($form);

        if ($data = Mage::registry('zblocks_data')) {
            $form->setValues($data);
        }
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _addGeneralFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_general', array('legend' => $this->__('General Information')));

        $fieldset->addField('block_title', 'text', array(
                'name'  => 'block_title',
                'label' => $this->__('Block Title'),
                'title' => $this->__('Block Title'),
        ));

        $fieldset->addField('creation_time', 'hidden', array(
                'name'  => 'creation_time',
                'value' => '',
        ));

        $fieldset->addField('block_is_active', 'select', array(
                'label'   => $this->__('Status'),
                'title'   => $this->__('Status'),
                'name'    => 'block_is_active',
                'options' => array(
                    '1' => $this->__('Enabled'),
                    '0' => $this->__('Disabled'),
                ),
        ));

        if (Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_ids', 'hidden', array(
                    'name'  => 'store_ids[]',
                    'value' => ''
            ));
        } else {
            $fieldset->addField('store_ids', 'multiselect', array(
                    'name'     => 'store_ids[]',
                    'label'    => $this->__('Store View'),
                    'title'    => $this->__('Store View'),
                    'required' => true,
                    'values'   => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
        }

        $fieldset->addField('block_sort_order', 'text', array(
                'name'  => 'block_sort_order',
                'label' => $this->__('Sort Order'),
                'title' => $this->__('Sort Order'),
        ));

        return $this;
    }

    protected function _addPositionFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_position', array('legend' => $this->__('Block Position')));

        $fieldset->addField('block_position', 'select', array(
                'label'  => $this->__('Block Position'),
                'title'  => $this->__('Block Position'),
                'name'   => 'block_position',
                'values' => Mage::getModel('zblocks/source_position')->toOptionArray(),
        ));
        $learnMoreUrl = AW_Zblocks_Helper_Data::DOCUMENTATION_CUSTOM_POSITION_URL;
        $fieldset->addField('block_position_custom', 'text', array(
                'name'    => 'block_position_custom',
                'label'   => $this->__('Custom Position Name'),
                'title'   => $this->__('Custom Position Name'),
                'note'    => $this->__(
                        "Will be used while changing template of corresponding CMS page or XML layout. <a href='%s' target='_blank'>Learn More.</a>",
                        $learnMoreUrl
                    ),
        ));

        $fieldset->addField('is_use_category_filter_custom', 'select', array(
                'label'   => $this->__("Use category filter for custom position"),
                'title'   => $this->__("Use category filter for custom position"),
                'name'    => 'is_use_category_filter_custom',
                'options' => array(
                    '1' => $this->__('Enabled'),
                    '0' => $this->__('Disabled'),
                ),
        ));

        $this->getChild('form_after')
            ->addFieldMap('block_position', 'block_position')
            ->addFieldMap('block_position_custom', 'block_position_custom')
            ->addFieldMap('is_use_category_filter_custom', 'is_use_category_filter_custom')
            ->addFieldDependence('block_position_custom', 'block_position', AW_Zblocks_Model_Source_Position::CUSTOM_POSITION)
            ->addFieldDependence('is_use_category_filter_custom', 'block_position', AW_Zblocks_Model_Source_Position::CUSTOM_POSITION);

        return $this;
    }

    protected function _addRotationFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('zblocks_rotation', array('legend' => $this->__('Display Items and Rotation')));

        $fieldset->addField('rotator_mode', 'select', array(
                'label'   => $this->__('Display Items'),
                'title'   => $this->__('Display Items'),
                'name'    => 'rotator_mode',
                'options' => Mage::helper('zblocks')->getRotatorModesToOptionsArray(),
        ));

        $fieldset->addField('representation_mode', 'select', array(
                'label'   => $this->__('Representation'),
                'title'   => $this->__('Representation'),
                'name'    => 'representation_mode',
                'options' => Mage::helper('zblocks')->getRepresentationModeToOptionsArray()
        ));

        $fieldset->addField('slider_rotator_interval', 'text', array(
            'name'  => 'slider_rotator_interval',
            'label' => $this->__('Rotation interval, sec'),
            'title' => $this->__('Rotation interval, sec'),
            'class' => 'validate-number validate-zero-or-greater',
        ));

        $this->getChild('form_after')
            ->addFieldMap('rotator_mode', 'rotator_mode')
            ->addFieldMap('representation_mode', 'representation_mode')
            ->addFieldMap('slider_rotator_interval', 'slider_rotator_interval')
            ->addFieldMap('slider_width', 'slider_width')
            ->addFieldMap('slider_height', 'slider_height')
            ->addFieldDependence('representation_mode', 'rotator_mode', AW_Zblocks_Helper_Data::ROTATOR_MODE_SHOW_ALL)
            ->addFieldDependence('slider_rotator_interval', 'representation_mode', AW_Zblocks_Helper_Data::REPRESENTATION_MODE_SLIDER)
            ->addFieldDependence('slider_rotator_interval', 'rotator_mode', AW_Zblocks_Helper_Data::ROTATOR_MODE_SHOW_ALL);

        return $this;
    }
}
