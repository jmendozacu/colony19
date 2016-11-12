<?php

class SDW_Provider_Model_Observer {

	public function sendMail($schedule) {
		$collection = Mage::getModel('provider/provider')->getCollection();
		$collection->getSelect()->join(array('provider_order' => 'mgt_sdw_provider_order'),
										'provider_order.id_provider = main_table.id_provider',
										array('id_provider_order'=>'id_provider_order',
											'order_create'=>'order_create',
											'product_id'=>'product_id',
											'quantity'=>'quantity',
											'provider_order_id'=>'provider_order_id',
											'status'=>'status'));
		$collection->addFieldToFilter('provider_order.status', 'Livraison');
		$collection->getSelect()->group(array('id_provider'));
	
		foreach($collection as $providers){
			$provider = $providers->getData();

			Mage::getSingleton('provider/email')->provider($provider);
		}
	}
}