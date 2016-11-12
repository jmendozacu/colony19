<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Revendeur extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		foreach($_product->group_price as $groupprice){
			if($groupprice['cust_group'] == 3){
				return '<input type="text" onblur="updateRevendeur('.$row->getId().')" id="prix_revendeur_'.$row->getId().'" class="product-price-input input-text validate-number" name="price_revendeur['.$row->getId().']" value="'.number_format($groupprice['price'],2,',','').'"/> €';
			}
		}
		
		return '<input type="text" onblur="updateRevendeur('.$row->getId().')" id="prix_revendeur_'.$row->getId().'" class="product-price-input input-text validate-number" name="price_revendeur['.$row->getId().']" value=""/> €';
    }
}