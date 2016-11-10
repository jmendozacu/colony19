<?php
  
class Activo_AdvancedSearch_Model_Adminhtml_System_Source_Similaritylevels
{
    public function toOptionArray()
    {
        return array(
            //array('value'=>'', 'label'=>''),
            array('value'=>1, 'label'=>Mage::helper('advancedsearch')->__('1 Most Similar, Less Alternatives')),
            array('value'=>2, 'label'=>Mage::helper('advancedsearch')->__('2')),
            array('value'=>3, 'label'=>Mage::helper('advancedsearch')->__('3')),
            array('value'=>4, 'label'=>Mage::helper('advancedsearch')->__('4')),
            array('value'=>5, 'label'=>Mage::helper('advancedsearch')->__('5 Least Similar, More Alternatives')),
        );
    }
}
