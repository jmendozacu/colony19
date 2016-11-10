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

class Activo_AdvancedSearch_Block_Adminhtml_Form_Field_Searchableattr extends Mage_Core_Block_Html_Select
{
    /*
     * Searchable attributes cache
     */
    protected $_searchableAttributes;

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function _getSearchableAttributes()
    {
        if (!$this->_searchableAttributes) 
        {
            $aCollection = Mage::getResourceModel('catalog/product_attribute_collection')->addSearchableAttributeFilter();
            
            $this->_searchableAttributes = array();
            foreach ($aCollection as $attribute)
            {
                $this->_searchableAttributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
                //$this->_searchableAttributes[] = array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel());
            }
        }

        return $this->_searchableAttributes;
    }
    
    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            foreach ($this->_getSearchableAttributes() as $code => $label) {
                $this->addOption($code, addslashes($label));
            }
        }
        return parent::_toHtml();
    }
}
