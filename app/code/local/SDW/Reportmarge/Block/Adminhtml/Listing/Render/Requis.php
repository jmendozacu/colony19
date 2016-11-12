<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Requis extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$productReport = Mage::getResourceModel('reports/product_sold_collection')
						->addAttributeToFilter('entity_id', $row->entity_id)
						->addOrderedQty();
						
		$qty = $productReport->getFirstItem()->getOrderedQty();
		
		$now = time();
		
		$diff = $now - strtotime($row->created_at);
		
		$semaine = $diff / (60 * 60 * 24 * 7);
		
		$qtySemaine = $qty / $semaine;
		
		$providerModel = Mage::getModel('provider/provider')->load($row->provider);
		
		$data = $providerModel->getData();
		
		$necessaire = $qtySemaine * $data['delai'] - $row->qty;
		
		if($necessaire > 0)	return ceil($necessaire);
		else return ;
    }
} 