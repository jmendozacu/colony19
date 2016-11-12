<?php
class SDW_Weighthistory_Block_Adminhtml_Listing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_blockGroup = 'weighthistory';
        $this->_controller = 'adminhtml_listing';
        $this->_headerText = $this->__('Weight History');
         
        parent::__construct();
		$this->removeButton('add');
    }
}
