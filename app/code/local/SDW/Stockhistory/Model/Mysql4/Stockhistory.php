<?php
class SDW_Stockhistory_Model_Mysql4_Stockhistory extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('stockhistory/stockhistory', 'id_log');
	}
}