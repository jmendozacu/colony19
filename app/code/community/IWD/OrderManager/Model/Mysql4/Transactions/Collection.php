<?php

class IWD_OrderManager_Model_Mysql4_Transactions_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('iwd_ordermanager/transactions');
    }

    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $unionSelect = clone $this->getSelect();

        $unionSelect->reset(Zend_Db_Select::ORDER);
        $unionSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $unionSelect->reset(Zend_Db_Select::LIMIT_OFFSET);

        $countSelect = clone $this->getSelect();
        $countSelect->reset();
        $countSelect->from(array('a' => $unionSelect), 'COUNT(*)');

        return $countSelect;
    }
}
