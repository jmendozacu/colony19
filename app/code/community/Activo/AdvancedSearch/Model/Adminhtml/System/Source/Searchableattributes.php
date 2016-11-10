<?php

class Activo_AdvancedSearch_Model_Adminhtml_System_Source_Searchableattributes
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) 
        {
            $aCollection = Mage::getResourceModel('catalog/product_attribute_collection')->addSearchableAttributeFilter();
            
            $this->_options = array();
            foreach ($aCollection as $attribute)
            {
                $this->_options[] = array('value' => $attribute->getAttributeCode(), 'label' => $attribute->getFrontendLabel());
            }
        }

        return $this->_options;
    }
}
