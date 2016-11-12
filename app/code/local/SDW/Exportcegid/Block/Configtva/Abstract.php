<?php

class SDW_Exportcegid_Block_Configtva_Abstract extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    protected $_group;
 
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);
        $productTaxClasses = Mage::getModel('tax/class')->getCollection();
        
        foreach($productTaxClasses as $productTaxClass)
        {
            if($productTaxClass->getClassType()=="CUSTOMER")continue;
            $html.= $this->_getFieldHtml($element, $productTaxClass);
        }
        $html .= $this->_getFooterHtml($element);
 
        return $html;
    }
    
    protected function _getFieldHtml($fieldset,Mage_Tax_Model_Class $productTaxClass)
    {
        $configData = $this->getConfigData();
        $path = 'configuration_tax/'.$this->_group.'/class_'.$productTaxClass->getId();
        $inherit = !isset($configData[$path]);
        $data = $inherit
                ?   (string)$this->getForm()->getConfigRoot()->descend($path)
                :   $configData[$path];
                
 
        $e = new Varien_Object(array('show_in_default'=>1, 'show_in_website'=>1));
 
        $field = $fieldset->addField($path, 'text',array(
                'name'          => 'groups['.$this->_group.'][fields][class_'.$productTaxClass->getId().'][value]',
                'label'         => $productTaxClass->getClassName(),
                'value'         => $data,
                'inherit'       => $inherit,
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
            ))->setRenderer(Mage::getBlockSingleton('adminhtml/system_config_form_field'));
 
        return $field->toHtml();
    }
    
    protected function _getFieldHtmlShipping($fieldset,$shippingWithTva)
    {
        $configData = $this->getConfigData();
        $path = 'configuration_tax/'.$this->_group.'/class_'.($shippingWithTva?"avec":"sans");
        $inherit = !isset($configData[$path]);
        $data = $inherit
                ?   (string)$this->getForm()->getConfigRoot()->descend($path)
                :   $configData[$path];
                
 
        $e = new Varien_Object(array('show_in_default'=>1, 'show_in_website'=>1));
 
        $field = $fieldset->addField($path, 'text',array(
                'name'          => 'groups['.$this->_group.'][fields][class_'.($shippingWithTva?"avec":"sans").'][value]',
                'label'         => ($shippingWithTva?"Transport avec tva":"Transport sans tva"),
                'value'         => $data,
                'inherit'       => $inherit,
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
            ))->setRenderer(Mage::getBlockSingleton('adminhtml/system_config_form_field'));
 
        return $field->toHtml();
    }
}