<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Semaine extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		if(!empty($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'],array("31.39.24.91","80.13.123.44","88.160.94.181")))
		{
			die("test");
		}


		$productReport = Mage::getResourceModel('reports/product_sold_collection')
						->addOrderedQty()
						->addAttributeToFilter('entity_id', $row->entity_id);
		$qty = $productReport->getFirstItem()->getOrderedQty();
		
		$now = time();
		$diff = $now - strtotime($row->created_at);
		$semaine = $diff / (60 * 60 * 24 * 7);
		$qtySemaine = $qty / $semaine;

		return number_format($qtySemaine, 2, '.',' ');
    }
}