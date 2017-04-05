<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{

    public function viewAction()
    {
        return parent::viewAction();
    }

}
				