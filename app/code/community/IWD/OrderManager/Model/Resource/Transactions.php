<?php

class IWD_OrderManager_Model_Resource_Transactions extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('iwd_ordermanager/transactions', 'id');
    }
}
