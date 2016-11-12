<?php
  
class SDW_Pricehistory_Model_Observer
{
	static protected $_singletonFlag = false;
	
	public function saveProductTabData(Varien_Event_Observer $observer)
	{
		if (self::$_singletonFlag)return;
		self::$_singletonFlag = true;

		$product = $observer->getEvent()->getProduct();

		try
		{
			$tax=Mage::getModel("tax/class")->load($product->getTaxClassId());

			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->exec(sprintf("UPDATE mgt_sdw_price_history SET value_to=NOW() WHERE value_to='0000-00-00 00:00:00' AND entity_id=%d",$product->getId()));
			$write->exec(sprintf("INSERT INTO mgt_sdw_price_history(entity_id,value_from,value_to,value_pa,value_pad,value_pht,value_tva) VALUES (%d,NOW(),'0000-00-00 00:00:00',%f,%f,%f,'%s')",
				$product->getId(),
				$product->getPrixAchat(),
				$product->getPrixAchatDollar(),
				$product->getPrice(),
				$tax->getClassName()
			));
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
	}
}