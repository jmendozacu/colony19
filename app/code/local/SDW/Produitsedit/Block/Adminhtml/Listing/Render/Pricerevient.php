<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Pricerevient extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		if(!$row->provider)return '<span id="prix_revient_'.$row->getId().'" data-transport="0">'.number_format($_product->getData('prix_achat'), 2,',',''). ' €</span>'; 
		
		$providerModel = Mage::getModel('provider/provider')->load((int)$row->provider);
		
		$data = $providerModel->getData();
		
		return '<span id="prix_revient_'.$row->getId().'" data-transport="'.$data['transport'].'">'.number_format($_product->getData('prix_achat') + ($_product->getData('prix_achat') * $data['transport'] / 100), 2,',',''). ' €</span>';
    }
}