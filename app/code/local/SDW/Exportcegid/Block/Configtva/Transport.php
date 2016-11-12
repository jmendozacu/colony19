<?php

class SDW_Exportcegid_Block_Configtva_Transport extends SDW_Exportcegid_Block_Configtva_Abstract
{
    protected $_group="transport";
 
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $html.= $this->_getFieldHtmlShipping($element, true);
        $html.= $this->_getFieldHtmlShipping($element, false);
        $html .= $this->_getFooterHtml($element);
 
        return $html;
    }
} 
