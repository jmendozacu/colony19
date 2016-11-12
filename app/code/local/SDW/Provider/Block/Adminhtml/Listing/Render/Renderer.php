<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$data = $this->loadData($row);
		return $data[$this->getColumn()->getIndex()];
	}


	public function loadData($row)
	{			
		static $sharedmemory=array();
		if(isset($sharedmemory[$row->entity_id]))return $sharedmemory[$row->entity_id];


		$now = time();
		$start = strtotime($row->created_at);
		$diff = $now - $start;
		if($diff>86400 * 7 * 52)
		{
			$start=$now-86400 * 7 * 52;
			$diff = $now - $start;
		}

		$productReport = Mage::getResourceModel('reports/product_collection')
						->addOrderedQty(date("c",$start),date("c",$now))
						->addAttributeToFilter('entity_id', $row->entity_id)
						->setOrder('ordered_qty', 'desc')
						->getFirstItem();
		$qty = $productReport->getOrderedQty();
		
		
		$semaine = $diff / (86400 * 7);
		$qtySemaine = $qty / $semaine;
		
		$annee = $diff / (86400 * 7 * 52);
		$qtyAnnee = $qty / $annee;
		
		

		$providerModel = Mage::getModel('provider/provider')->load($row->provider);
		$data = $providerModel->getData();
		
		$necessaire = $qtySemaine * $data['delai'] - $row->qty;
		
		if($necessaire > 0)	{
			$alerte = '<span style="color:red;">A</span>';
			$requis = ceil($necessaire);
		}
		else {
			$alerte = '';
			$requis = '';
		}


		$sharedmemory[$row->entity_id]=array(
			"semaine" 			=> number_format($qtySemaine, 2, '.',' '), 
			"annee" 			=> number_format($qtyAnnee, 2, '.',' '),
			"qty" 				=> number_format($row->qty, 0, '.',' '),
			"alerte" 			=> $alerte,
			"requis" 			=> $requis,
		);

		return $sharedmemory[$row->entity_id];
	}
} 