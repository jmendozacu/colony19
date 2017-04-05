<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{

    protected function _initProduct()
    {
        Mage::log("test module",0,"debug_agentom_video.log");
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);



        return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
    }


    public function viewAction()
    {
        // Get initial data from request
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');
        $specifyOptions = $this->getRequest()->getParam('options');

        // Prepare helper and params
        $viewHelper = Mage::helper('catalog/product_view');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);
        $params->setSpecifyOptions($specifyOptions);

        $category = Mage::getModel('catalog/cateogy')->load($categoryId);

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

        // Render page
        try {
            $viewHelper->prepareAndRender($productId, $this, $params);
        } catch (Exception $e) {
            if ($e->getCode() == $viewHelper->ERR_NO_PRODUCT_LOADED) {
                if (isset($_GET['store'])  && !$this->getResponse()->isRedirect()) {
                    $this->_redirect('');
                } elseif (!$this->getResponse()->isRedirect()) {
                    $this->_forward('noRoute');
                }
            } else {
                Mage::logException($e);
                $this->_forward('noRoute');
            }
        }
    }
}
				