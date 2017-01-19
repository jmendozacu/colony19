<?php
require_once "Mage/Catalog/controllers/CategoryController.php";  
class Agentom_Video_Catalog_CategoryController extends Mage_Catalog_CategoryController{

    public function postDispatch()
    {
        parent::postDispatch();
        Mage::dispatchEvent('controller_action_postdispatch_adminhtml', array('controller_action' => $this));
    }


}
				