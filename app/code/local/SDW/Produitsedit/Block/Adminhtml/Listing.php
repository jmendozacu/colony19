<?php
class SDW_Produitsedit_Block_Adminhtml_Listing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_blockGroup = 'produitsedit';
        $this->_controller = 'adminhtml_listing';
        $this->_headerText = $this->__('Gestion des Prix Produits');
         
        parent::__construct();
		
		$this->_removeButton('add');
    }
}
