<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Quantite extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
     public function render(Varien_Object $row)
    {
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$providerOrder = $read->fetchAll("SELECT * FROM `mgt_sdw_provider_order` 
													LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
													WHERE `mgt_sdw_provider_order`.provider_order_id=".$row->provider_order_id."
													AND `mgt_sdw_provider_order`.id_provider=".(int) $this->getRequest()->getParam('id').'
													ORDER BY product_id DESC');
													
						
		$sku="";
		$first = true;
		foreach($providerOrder as $providerProduct){
			if(!$first)$sku .='<br/>';
			$sku .=  number_format($providerProduct['quantity'], 0, '.',' ');
			$first = false;
		}
	
		return $sku;
    }
}