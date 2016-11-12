<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Achat extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		return '<input type="text" onblur="updateAchat('.$row->getId().')" id="prix_achat_'.$row->getId().'" class="product-price-input input-text validate-number" name="prix_achat['.$row->getId().']" value="'.number_format($_product->getData('prix_achat'),2,',','').'"/> €';
    }
}