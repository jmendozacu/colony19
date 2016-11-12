<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Deleteorder extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
     public function render(Varien_Object $row)
    {
		if($row->status == 'Livré') return '';
		return '<a href="'.$this->getUrl('*/*/deleteorder', array('id'=>$this->getRequest()->getParam('id'),'id_order' => $row->provider_order_id,'grid'=>'commande')).'">Supprimer</a>';
    }
}