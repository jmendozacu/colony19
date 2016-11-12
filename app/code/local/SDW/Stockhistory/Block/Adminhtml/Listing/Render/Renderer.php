<?php

class SDW_Stockhistory_Block_Adminhtml_Listing_Render_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$data = $this->loadData($row);
		return $data[$this->getColumn()->getIndex()];
	}


	public function loadData($row)
	{
		$sharedmemory=array();
		
		$date = Mage::app()->getLocale()->date($row->date,'YYYY-MM-dd HH:mm:ss',null,true);
		$stock = $row->stock;


		$sharedmemory[$row->id_log]=array(
			"date" 			=> $date,
			"stock" 		=> $stock
		);

		return $sharedmemory[$row->id_log];
	}
}