<?php
class SDW_Provider_Block_Adminhtml_Listing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_blockGroup = 'provider';
        $this->_controller = 'adminhtml_listing';
        $this->_headerText = $this->__('Gestion des Fournisseurs');
         
        parent::__construct();
    }
}
