<?php

/**
 * Iksanika llc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.iksanika.com/products/IKS-LICENSE.txt
 *
 * @category   Iksanika
 * @package    Iksanika_Stockmanage
 * @copyright  Copyright (c) 2013 Iksanika llc. (http://www.iksanika.com)
 * @license    http://www.iksanika.com/products/IKS-LICENSE.txt
 */

class Iksanika_Stockmanage_Block_Catalog_Product extends Mage_Adminhtml_Block_Catalog_Product
{
    
    public function __construct()
    {
        parent::__construct();
        $this->_headerText = Mage::helper('stockmanage')->__('Stock Inventory Manager');
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->_removeButton('add_new');
        $this->setTemplate('iksanika/stockmanage/catalog/product.phtml');
        $this->setChild('grid', $this->getLayout()->createBlock('stockmanage/catalog_product_grid', 'product.productupdater'));
    }
    /*
    public function getStoreSwitcherHtml()
    {
        if(!$this->isSingleStoreMode())
        {
            return $this->getChildHtml('store_switcher');
        }
    }*/
}