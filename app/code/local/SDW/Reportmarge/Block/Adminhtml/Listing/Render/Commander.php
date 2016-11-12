<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Commander extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Input
{	

	public function setColumn($column)
    {
        $column->setEditable(true);
        return parent::setColumn($column);
    }

    public function render(Varien_Object $row)
    {
        $value = $this->_getValue($row);
        $value = $value != '' ? $value : '&nbsp;';
		
		$provider_order_id = (int)$this->getRequest()->getParam('provider_order_id');
		
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$providerOrder = $read->fetchRow("SELECT * FROM mgt_sdw_provider_order
											WHERE mgt_sdw_provider_order.provider_order_id = ".$provider_order_id."
													AND mgt_sdw_provider_order.product_id = ".(int) $row->entity_id."
													ORDER BY product_id DESC");			

        if (!$row->isComposite()) {
            $value .= '<input type="text" class="input-text validate-number" name="product_quantity['.$row->getId().']" value="'.$providerOrder['quantity'].'"/>';
        }
				
        return $value;
    }

    public function renderExport(Varien_Object $row)
    {
        return $this->_getValue($row);
    }
}