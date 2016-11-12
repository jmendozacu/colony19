<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Pricettc extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		$_priceIncludingTax = Mage::helper('tax')->getPrice($_product, $_product->getPrice());
	
		return '<input type="text" onblur="updatePricettc('.$row->getId().')" id="prix_ttc_'.$row->getId().'" class="product-price-input input-text validate-number" name="price_ttc['.$row->getId().']" value="'.number_format($_priceIncludingTax,2,',','').'"/> €';
    }
} 