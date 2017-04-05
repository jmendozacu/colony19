<?php
require_once "Mage/Catalog/controllers/ProductController.php";  
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{

    protected function _initProduct()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);

        if($categoryId){
            $category = Mage::getModel('catalog/category')->load($categoryId);
        }

        if($category->getHiddenFromCustomer() && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $authorizedIds = explode(",",$customerData->getAllowedCategoryIds());
            if(!in_array($category->getId(),$authorizedIds)){
                Mage::getSingleton('core/session')->addError($this->__('Accès réservé aux personnes ayant acheté le pack vidéo.'));
                $this->_redirect('customer/account/login');
                return false;
            }
        }elseif($category->getHiddenFromCustomer()){

            Mage::log("not logged in",0,"debug_agentom_video.log");

            Mage::getSingleton('core/session')->addError($this->__('Si vous avez accès au pack vidéo, veuillez-vous connecter.'));
            $this->_redirect('customer/account/login');
            return false;
        }

        return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
    }

    public function viewAction(){

        return parent::viewAction();
    }


}
				