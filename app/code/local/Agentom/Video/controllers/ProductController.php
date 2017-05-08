<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_ProductController extends Mage_Catalog_ProductController
{
    /**
     * Product view action
     */
    public function viewAction()
    {
        $productId = (int)$this->getRequest()->getParam('id');
        $product = Mage::getModel('catalog/product')->load($productId);

        $cats = $product->getCategoryIds();

        $isHidden = false;

        foreach ($cats as $category_id) {
            $_cat = Mage::getModel('catalog/category')->load($category_id);
            if ($_cat->getHiddenFromCustomer()) {
                $isHidden = true;
                $hiddenCat = $_cat;
            }
        }

        if (Mage::getSingleton('customer/session')->isLoggedIn() && $isHidden) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $authorizedIds = explode(",", $customerData->getAllowedCategoryIds());
            if (!in_array($hiddenCat->getId(), $authorizedIds)) {
                Mage::getSingleton('core/session')->addError($this->__('Accès réservé aux personnes ayant acheté le pack vidéo.'));
                $this->_redirect('video/index/index');
                return false;
            } else {
                return parent::viewAction();
            }
        } else {
            Mage::getSingleton('core/session')->addError($this->__('Si vous avez accès au pack vidéo, veuillez-vous connecter.'));
            $this->_redirect('video/index/index');
        }

        return parent::viewAction();
    }
}