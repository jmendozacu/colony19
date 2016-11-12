<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Reffournisseur extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
		return '<input type="text" class="input-text validate-number" name="ref_fournisseur['.$row->getId().']" value="'.$row->ref_fournisseur.'" style="min-width: 50px;"/> ';
    }
}