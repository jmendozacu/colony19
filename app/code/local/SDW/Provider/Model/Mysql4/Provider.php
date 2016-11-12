<?php
class SDW_Provider_Model_Mysql4_Provider extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct()
	{
		$this->_init('provider/provider', 'id_provider');
	}
}