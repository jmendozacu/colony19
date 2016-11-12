<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Createat extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		return '<input type="text" class="product-price-input input-text validate-number" name="date_create['.$row->getId().']" value="'.date('d-m-Y',strtotime($_product->getData('created_at'))).'"/>';
    }
}