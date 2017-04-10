<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_ProductController extends Mage_Catalog_ProductController{
    /**
     * Product view action
     */
    public function viewAction()
    {
        return parent::viewAction();
    }
}
				