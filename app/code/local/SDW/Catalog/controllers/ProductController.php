<?php

require_once Mage::getModuleDir('controllers', 'Mage_Catalog').'/ProductController.php';


/**
 * Product controller
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class SDW_Catalog_ProductController extends Mage_Catalog_ProductController
{
    /**
     * Product view action
     */
    public function viewAction()
    {
        // Get initial data from request
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');
        $specifyOptions = $this->getRequest()->getParam('options');


        $produit = Mage::getModel('catalog/product')->load($productId);
            
        if(!$categoryId && $produit->attribute_set_id==9 && count($produit->getCategoryIds())) // Si pas de catégorie, on redirige vers la fiche catégorie, 
        {
            $categories=$produit->getCategoryIds();
            $category=Mage::getModel("catalog/category")->load($categories[0]);
            header("Location: ".$produit->getUrlPath($category));
            exit;
        }
        elseif($categoryId==252)
        {
            $urlCms=Mage::getConfig()->getNode("global/resources/localconfig/pack_categorie_$categoryId");
            if($produit->getPrice()!=0)
            {
                $isallowed=false;

                if(Mage::getSingleton('customer/session')->isLoggedIn())
                {
                    $orders = Mage::getModel('sales/order')->getCollection()
                            ->addAttributeToFilter("customer_id", Mage::getSingleton('customer/session')->getCustomerId())
                            ->addAttributeToFilter('state', 'complete')
                        ;
                    $purchased = array();
                    foreach ($orders as $order)
                        foreach($order->getAllItems() as $item)
                            $isallowed|=($item->getProductId()==5357 || $item->getProductId()==5360);
                }

                if(!$isallowed)
                {
                    header("Location: ".$urlCms);
                    exit;
                }
            }
        }
        else
        {
            $packProductId=(int)Mage::getConfig()->getNode("global/resources/localconfig/pack_categorie_$categoryId");
            if($produit->getPrice()!=0 && $packProductId!=0)
            {
                $isallowed=false;

                if(Mage::getSingleton('customer/session')->isLoggedIn())
                {
                    $orders = Mage::getModel('sales/order')->getCollection()
                            ->addAttributeToFilter("customer_id", Mage::getSingleton('customer/session')->getCustomerId())
                            ->addAttributeToFilter('state', 'complete')
                        ;

                    $purchased = array();
                    foreach ($orders as $order)
                        foreach($order->getAllItems() as $item)
                            $isallowed|=($item->getProductId()==$packProductId);
                }

                if(!$isallowed)
                {
                    $requiredproduct=Mage::getModel('catalog/product')->load($packProductId);
                    header("Location: ".$requiredproduct->getProductUrl());
                    exit;
                }
            }
        }

        return parent::viewAction();
    }
}
