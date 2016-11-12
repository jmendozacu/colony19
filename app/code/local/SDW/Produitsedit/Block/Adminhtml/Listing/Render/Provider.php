<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Provider extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$collection = Mage::getModel('provider/provider')->getCollection();

		$options = '<option value="" data-transport=""></option>';
		foreach($collection as $value){
			$data = $value->getData();
			if($row->provider == $data['id_provider'])
				$options .= '<option value="'.$data['id_provider'].'" selected="selected" data-transport="'.$data['transport'].'">'.$data['code'].'-'.$data['sociale'].'</option>';
			else 
				$options .= '<option value="'.$data['id_provider'].'" data-transport="'.$data['transport'].'">'.$data['code'].'-'.$data['sociale'].'</option>';
		}
		return '<select  style="min-width: 55px;" class="provider-select-input" onchange="updateProvider('.$row->getId().')" id="provider_'.$row->getId().'" name="provider['.$row->getId().']" >'.$options.'</select>';
    }
}