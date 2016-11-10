<?php
class Codazon_Themeframework_Model_System_Config_Source_Categorysearchtype
{
	public function toOptionArray()
    {
		$helper = Mage::helper('themeframework');
        return array(
			array('value' => 0, 'label' => $helper->__('All levels')),
            array('value' => 1, 'label' => $helper->__('First level')),
            array('value' => 2, 'label' => $helper->__('Two first levels')),
			array('value' => 3, 'label' => $helper->__('Three first levels')),
        );
    }
}