<?php
require_once "Mage/Catalog/controllers/CategoryController.php";  
class Agentom_Video_Catalog_CategoryController extends Mage_Catalog_CategoryController{

    protected function _initCatagory()
    {
        die();
        $category = parent::_initCatagory();

        Mage::log("debug : attr cat : " . $category->getHiddenFromCustomer(),0,"debug_agentom_video.log");

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

        return $category;
    }

    // TODO : add powell case
//    protected function _initCatagory()
//    {
//        $category = parent::_initCatagory();
//
//        if (
//            Mage::app()->getWebsite()->getCode() === 'powell'
//            &&
//            !Mage::getSingleton('customer/session')->isLoggedIn()
//            &&
//            $category->getLevel() > 2
//        ) {
//            Mage::getSingleton('core/session')->addError($this->__('Accès réservé aux professionnels. Nous vous remercions de saisir vos identifiants.'));
//            $this->_redirect('customer/account/login');
//            return false;
//        }
//
//        return $category;
//    }


}
				