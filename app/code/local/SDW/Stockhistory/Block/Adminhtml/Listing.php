<?php
class SDW_Stockhistory_Block_Adminhtml_Listing extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_blockGroup = 'stockhistory';
        $this->_controller = 'adminhtml_listing';
        $this->_headerText = $this->__('Stock File History');
         
        parent::__construct();
    }
}
