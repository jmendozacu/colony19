<?php
  
class SDW_Stockhistory_Model_Stockhistory extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('stockhistory/stockhistory');
	}
}