<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Specialttc extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
		
		$_priceIncludingTax = Mage::helper('tax')->getPrice($_product, $price);
	
		return '<input type="text" onblur="updateSpecial('.$row->getId().')" id="special_ttc_'.$row->getId().'" class="product-price-input input-text validate-number" name="special_price['.$row->getId().']" value="'.number_format($_priceIncludingTax ,2,',','').'"/> €';
    }
}