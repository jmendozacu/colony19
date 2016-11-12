<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Rendererstatus extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$data = $this->loadData($row);
		return $data[$this->getColumn()->getIndex()];
	}


	public function loadData($row)
	{			
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$providerOrder = $read->fetchAll("SELECT * FROM `mgt_sdw_provider_order` 
													LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
													WHERE `mgt_sdw_provider_order`.provider_order_id=".$row->provider_order_id."
													AND `mgt_sdw_provider_order`.id_provider=".(int) $this->getRequest()->getParam('id').'
													ORDER BY product_id DESC');
													
		
		$qty="";		
		$sku="";		
		$name="";
		$first = true;
		foreach($providerOrder as $providerProduct){
			if(!$first){
				$name .='<br/>';
				$sku .='<br/>';
				$qty .='<br/>';
			}
			$_product = Mage::getModel('catalog/product')->load($providerProduct['product_id']);
			$name .= $_product->getName();
			$sku .= $_product->getSku();
			$qty .= number_format($providerProduct['quantity'], 0, '.',' ');
			$first = false;
		}

		
		$providerModel = Mage::getModel('provider/provider')->load((int)$this->getRequest()->getParam('id'));
		$data = $providerModel->getData();		
		$create = strtotime($row->order_create);
		$livraison = $create + ($data['delai'] * 7 * 24 * 60 * 60);

		return array(
			"name" 			=> $name, 
			"sku" 			=> $sku, 
			"qty" 			=> $qty, 
			"order_estimate" 		=> Mage::getModel('core/date')->date('d M. Y' , $livraison), 
		);
	}
} 