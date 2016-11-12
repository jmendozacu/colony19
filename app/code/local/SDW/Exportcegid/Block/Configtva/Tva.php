<?php

class SDW_Exportcegid_Block_Configtva_Tva extends SDW_Exportcegid_Block_Configtva_Abstract
{
    protected $_group="tva";
 
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $productTaxClasses = Mage::getModel('tax/class')->getCollection();
        
        foreach($productTaxClasses as $productTaxClass)
        {
            if($productTaxClass->getClassType()=="CUSTOMER")continue;
            $html.= $this->_getFieldHtml($element, $productTaxClass);
        }
        $html.= $this->_getFieldHtmlShipping($element, true);
        $html .= $this->_getFooterHtml($element);
 
        return $html;
    }
} 
 
