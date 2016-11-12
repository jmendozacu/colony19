<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Priceht extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
	
		return number_format($_product->getPrice(),2,',','').' €';
    }
}