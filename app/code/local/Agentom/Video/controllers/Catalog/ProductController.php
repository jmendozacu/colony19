<?php
require_once 'Mage/Catalog/controllers/ProductController.php';
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{
    public function viewAction()
    {
        die('debug');
        return parent::viewAction();
    }
}
				