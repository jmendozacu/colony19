<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Margerevendeur extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$_product = Mage::getModel('catalog/product')->load($row->entity_id);
		
		foreach($_product->group_price as $groupprice){
			if($groupprice['cust_group'] == 3){
				if($row->provider) {
					$providerModel = Mage::getModel('provider/provider')->load((int)$row->provider);
					
					$data = $providerModel->getData();
					
					$prix_revient = $_product->getData('prix_achat') + ($_product->getData('prix_achat') * $data['transport'] / 100);
				}
				else{
					$prix_revient = $_product->getData('prix_achat');
				}
		
				return '<span id="marge_revendeur_'.$row->getId().'" data-groupprice='.$groupprice['price'].'>'.number_format((($groupprice['price'] - $prix_revient ) / $groupprice['price']) * 100, 1,',',''). '%</span>';
			}
		}

		return '<span id="marge_revendeur_'.$row->getId().'" data-groupprice=0></span>';
    }
} 