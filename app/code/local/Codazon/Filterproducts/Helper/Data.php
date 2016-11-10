<?php
class Codazon_Filterproducts_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_config;
	protected $_labelBlock;
	protected $_showLabel;
	
	public function getConfig(){
		if(!$this->_config)	{
			$this->_config = Mage::registry('theme_config');
		}
		return $this->_config;
	}
	protected function _getLabelBlock($template){
		if(!$this->_labelBlock){
			$this->_labelBlock = Mage::app()->getLayout()->createBlock('core/template')->setTemplate($template);
		}
		return $this->_labelBlock;
	}
	public function getProductLabel($product, $template = 'codazon_themeframework/product/label.phtml'){
		$labelBlock = $this->_getLabelBlock($template)->setProduct($product);
		$labels = array();
		if($this->isNewProduct($product)){
			$labels[] = array('label' => $this->__('New'), 'html_class' => 'lb-new');
		}
		if($this->isSaleProduct($product)){
			$html = round( ($product->getFinalPrice() - $product->getPrice())/($product->getPrice())*100 ).'%';
			$labels[] = array('label' => $html, 'html_class' => 'lb-sale');
		}
		$labelBlock->setLabels($labels);
		echo $labelBlock->toHtml();
	}
	public function isSaleProduct(Mage_Catalog_Model_Product $product){
		if ($product->getFinalPrice() < $product->getPrice()) {
			return true;
		}else{
			return false;	
		}
	}
	public function isNewProduct(Mage_Catalog_Model_Product $product)
	{
		$newsFromDate = $product->getNewsFromDate();
		$newsToDate   = $product->getNewsToDate();
		if (!$newsFromDate && !$newsToDate) {
			return false;
		}
		return Mage::app()->getLocale()
				->isStoreDateInInterval($product->getStoreId(), $newsFromDate, $newsToDate);
	}
}
	 