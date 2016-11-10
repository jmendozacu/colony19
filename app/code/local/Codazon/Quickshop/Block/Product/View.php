<?php
class Codazon_Quickshop_Block_Product_View extends Mage_Catalog_Block_Product_View
{
	protected function _construct()
	{
		parent::_construct();
		$this->_coreHelper = $this->helper('core/url');
		$this->_coreSession = Mage::getSingleton('core/session');
		$this->_productVisibility = Mage::getSingleton('catalog/product_visibility');
		$this->_logVisitor = Mage::getSingleton('log/visitor');
	}
	protected function _getUrlParams($product)
    {
		$url = $this->getRequest()->getParam('curUrl');
		if($url == null){
			$url = $this->helper('core/url')->getCurrentUrl();
		}
        return array(
            'product' => $product->getId(),
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $this->_coreHelper->getEncodedUrl($url),
            Mage_Core_Model_Url::FORM_KEY => $this->_coreSession->getFormKey()
        );
    }
	public function getAddToCompareUrl($product)
    {
        //return $this->helper('catalog/product_compare')->getAddUrl($product);
		return $this->getUrl('catalog/product_compare/add', $this->_getUrlParams($product));
    }
}