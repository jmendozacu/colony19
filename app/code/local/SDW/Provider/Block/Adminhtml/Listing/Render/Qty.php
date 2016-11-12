<?php

class SDW_Provider_Block_Adminhtml_Listing_Render_Qty extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
     public function render(Varien_Object $row)
    {
		return number_format($row->qty, 0, '.',' ');
    }
}