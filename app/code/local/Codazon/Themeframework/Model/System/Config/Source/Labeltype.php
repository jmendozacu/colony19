<?php
class Codazon_Themeframework_Model_System_Config_Source_Labeltype
{
	public function toOptionArray()
    {
		$helper = Mage::helper('themeframework');
        return array(
            array('value' => 'new', 'label' => $helper->__('New')),
            array('value' => 'sale', 'label' => $helper->__('Sale')),
        );
    }
}