<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Annee extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
     public function render(Varien_Object $row)
    {
		$productReport = Mage::getResourceModel('reports/product_sold_collection')
						->addOrderedQty()
						->addAttributeToFilter('entity_id', $row->entity_id);
	
		$qty = $productReport->getFirstItem()->getOrderedQty();
		
		$now = time();
		
		$diff = $now - strtotime($row->created_at);
		
		$annee = $diff / (60 * 60 * 24 * 7 * 52);
		
		$qtyAnnee = $qty / $annee;

		return number_format($qtyAnnee, 2, '.',' ');
    }
}