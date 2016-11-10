<?php
class Codazon_Themeframework_Model_System_Config_Source_Imagetype
{
	public function toOptionArray()
    {
		$helper = Mage::helper('themeframework');
        return array(
            array('value' => 'image', 'label' => $helper->__('Base Image')),
            array('value' => 'thumbnail', 'label' => $helper->__('Thumbnail')),
        );
    }
}