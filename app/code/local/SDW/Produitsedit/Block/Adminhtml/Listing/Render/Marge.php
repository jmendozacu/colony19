<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Marge extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);

		if($row->special_price == NULL){			
			$price = $_product->getPrice();
		}
		else {
			
			$special_to_date = strtotime($_product->getSpecialToDate());
			$special_from_date = strtotime($_product->getSpecialFromDate());
			$now = strtotime(Mage::getModel('core/date')->date('Y-m-d H:i:s'));

			$price = $row->special_price;
			/*
			if(($special_from_date < $now) &&( $now < $special_to_date))$price = $row->special_price;
			else $price = $_product->getPrice();
			*/
		}
		
		$providerModel = Mage::getModel('provider/provider')->load((int)$row->provider);
		$data = $providerModel->getData();		
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);		
		$prix_revient = $_product->getData('prix_achat') + ($_product->getData('prix_achat') * $data['transport'] / 100);

		return '<span id="marge_'.$row->getId().'" data-price='.$price.' data-prix_revient='.$prix_revient.'>'.number_format($price - $prix_revient, 2,',',''). ' €</span>';
    }
}