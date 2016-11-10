<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

class Activo_AdvancedSearch_Block_Adminhtml_Form_Field_Weightedattr extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Mage_CatalogInventory_Block_Adminhtml_Form_Field_Customergroup
     */
    protected $_attributesRenderer;

    /**
     * Retrieve group column renderer
     *
     * @return Mage_CatalogInventory_Block_Adminhtml_Form_Field_Customergroup
     */
    protected function _getAttributesRenderer()
    {
        if (!$this->_attributesRenderer) {
            $this->_attributesRenderer = $this->getLayout()->createBlock(
                'advancedsearch/adminhtml_form_field_searchableattr', '',
                array('is_render_to_js_template' => true)
            );
            $this->_attributesRenderer->setClass('weighted_attribute_select');
            $this->_attributesRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_attributesRenderer;
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('searchable_attr_code', array(
            'label' => Mage::helper('advancedsearch')->__('Searchable Attribute'),
            'renderer' => $this->_getAttributesRenderer(),
        ));
        $this->addColumn('weight', array(
            'label' => Mage::helper('advancedsearch')->__('Weight'),
            'style' => 'width:100px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('advancedsearch')->__('Add Weighted Attribute');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getAttributesRenderer()->calcOptionHash($row->getData('searchable_attr_code')),
            'selected="selected"'
        );
    }
}
