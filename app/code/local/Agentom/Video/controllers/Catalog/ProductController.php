<?php
require_once(Mage::getModuleDir('controllers','Mage_Catalog').DS.'ProductController.php');
class Agentom_Video_Catalog_ProductController extends Mage_Catalog_ProductController{

    /**
     * Initialize requested product object
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        $product = Mage::getModel('catalog/product')->load($productId);

        Mage::register('product', $product);

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);

        return Mage::helper('catalog/product')->initProduct($productId, $this, $params);
    }
    /**
     * Product view action
     */
    public function viewAction()
    {
        $this->_initProduct();
        return parent::viewAction();
    }
}
				