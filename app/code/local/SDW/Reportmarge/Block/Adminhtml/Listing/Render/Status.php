<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Select
{	
	public function render(Varien_Object $row)
    {
		$value = $row->getData();
		
		if($value['status'] == 'Livré') return $value['status'];
		
        $html = '<select name="'.$this->getColumn()->getName().'" style="width:100%;" onchange="updateStatus('.$value["provider_order_id"].','.$this->getRequest()->getParam('id').', this.options[this.selectedIndex].value); return false;">';
			$val = 'En attente';
			$selected = ( ($val == $value['status'] && (!is_null($value['status']))) ? ' selected="true"' : '' );
			$html.= '<option value="' . $val . '"' . $selected . '>' . $val . '</option>';
			
			$val = 'Livraison';
			$selected = ( ($val == $value['status'] && (!is_null($value['status']))) ? ' selected="true"' : '' );
			$html.= '<option value="' . $val . '"' . $selected . '>' . $val . '</option>';
        $html.='</select>';
        return $html;
    }
}