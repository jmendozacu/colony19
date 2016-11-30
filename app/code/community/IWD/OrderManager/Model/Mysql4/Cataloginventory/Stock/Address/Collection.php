<?php

class IWD_OrderManager_Model_Mysql4_Cataloginventory_Stock_Address_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('iwd_ordermanager/cataloginventory_stock_address');
    }
}