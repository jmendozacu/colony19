<?php
require_once "Mage/Catalog/controllers/ProductController.php";  
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{

    public function postDispatch()
    {
        parent::postDispatch();
        Mage::dispatchEvent('controller_action_postdispatch_adminhtml', array('controller_action' => $this));
    }


}
				