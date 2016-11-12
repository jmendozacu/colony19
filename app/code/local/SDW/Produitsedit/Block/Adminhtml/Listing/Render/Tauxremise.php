<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Tauxremise extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
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
		
		// Get the product's tax class' ID
		$taxClassId = $_product->getData("tax_class_id");
		// Get the tax rates of each tax class in an associative array
		$taxClasses = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass());
		// Extract the tax rate from the array
		$taxRate = $taxClasses["value_".$taxClassId];
		
		if($_product->getFinalPrice() == 0)return '<span id="taux_remise_'.$row->getId().'" data-taxRate='.(($taxRate/100) +1).'></span>';
		
		$_priceIncludingTax = Mage::helper('tax')->getPrice($_product, $_product->getPrice());
		
		$_priceSpecialIncludingTax = Mage::helper('tax')->getPrice($_product, $price);
		
		if($_product->getFinalPrice() == 0) return '<span id="taux_remise_'.$row->getId().'" data-taxRate='.(($taxRate/100) +1).'></span>';
		
		return '<span id="taux_remise_'.$row->getId().'" data-taxRate='.(($taxRate/100) +1).'>'.number_format(100-(($_priceSpecialIncludingTax * 100) / $_priceIncludingTax),0,',',''). '%</span>';
    }
} 