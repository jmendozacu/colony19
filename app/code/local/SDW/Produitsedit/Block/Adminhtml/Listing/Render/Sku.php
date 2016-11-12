<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Sku extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		return '<input type="text" class="input-text validate-number" name="sku['.$row->getId().']" value="'.$row->sku.'" style="min-width: 70px;"/> ';
    }
}