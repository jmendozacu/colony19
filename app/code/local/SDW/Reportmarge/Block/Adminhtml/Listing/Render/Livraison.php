<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Livraison extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		$providerModel = Mage::getModel('provider/provider')->load((int)$this->getRequest()->getParam('id'));
		
		$data = $providerModel->getData();
		
		$create = strtotime($row->order_create);

		$livraison = $create + ($data['delai'] * 7 * 24 * 60 * 60);
		
		return Mage::getModel('core/date')->date('d M. Y' , $livraison);
    }
}