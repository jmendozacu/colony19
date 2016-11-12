<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Couttransport extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		if(!$row->provider)	return '<span id="prix_transport_'.$row->getId().'"></span>';
		
		$providerModel = Mage::getModel('provider/provider')->load((int)$row->provider);
		
		$data = $providerModel->getData();
		
		if($data['transport'] == 0) return '<span id="prix_transport_'.$row->getId().'"></span>';
		
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		return '<span id="prix_transport_'.$row->getId().'">'.number_format($_product->getData('prix_achat') * $data['transport'] / 100, 2,',',''). ' €</span>';
    }
}