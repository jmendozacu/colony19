<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_ProductController extends Mage_Catalog_ProductController{
    /**
     * Product view action
     */
    public function viewAction()
    {
        $productId  = (int) $this->getRequest()->getParam('id');
        $product = Mage::getModel('catalog/product')->load($productId);

        $cats = $product->getCategoryIds();
        foreach ($cats as $category_id) {
            $_cat = Mage::getModel('catalog/category')->load($category_id);
            if($_cat->getHiddenFromCustomer()){
                Mage::getSingleton('core/session')->addError($this->__('Accès réservé aux personnes ayant acheté le pack vidéo.'));
                $this->_redirect('customer/account/login');
                return false;
            }
        }
        return parent::viewAction();
    }
}
				