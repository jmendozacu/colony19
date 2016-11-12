<?php
class SDW_Stockhistory_Model_Mysql4_Stockhistory_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
 {
	public function _construct()
	{
		parent::_construct();
		$this->_init('stockhistory/stockhistory');
	}
}