<?php
  
class SDW_Provider_Model_Provider extends Mage_Core_Model_Abstract
{
	public function _construct()
	{
		parent::_construct();
		$this->_init('provider/provider');
	}
}