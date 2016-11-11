<?php
class Codazon_Themeframework_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_config;
	protected $_labelBlock;
	protected $_showLabel;
	
	/*public function getConfig(){
		if(!$this->_config)	{
			$this->_config = Mage::registry('theme_config');
		}
		return $this->_config;
	}*/
	public function getConfig($fieldPath){
		return Mage::getStoreConfig($fieldPath);
	}
	
	public function bootstrapCssFile(){
		if($this->getConfig('codazon_settings/general/enable_rtl') == 0){
			return 'codazon/bootstrap/css/bootstrap.min.css';
		}else{
			return 'codazon/bootstrap/css/bootstrap-rtl.css';
		}
	}
	public function sliderJsFile(){
		if($this->getConfig('codazon_settings/general/enable_rtl') == 0){
			return 'codazon/owl.carousel/owl.carousel.min.js';
		}else{
			return 'codazon/owl.carousel/owl.carousel.rtl.min.js';
		}
	}
	public function productOptionsJsFile(){
		if(version_compare(Mage::getVersion(), '1.9.3.0', '>=')){
			return 'varien/product_options.js';
		}else{
			return null;
		}
	}
	
	protected function _getLabelBlock($template){
		if(!$this->_labelBlock){
			$this->_labelBlock = Mage::app()->getLayout()->createBlock('core/template')->setTemplate($template);
		}
		return $this->_labelBlock;
	}
	protected function _showLabel(){
		if(!$this->_showLabel){
			$this->_showLabel = ($this->getConfig('codazon_settings/category_view/show_label') == 1)?true:false;
		}
		return $this->_showLabel;
	}
	public function getProductLabel($product, $template = 'codazon_themeframework/product/label.phtml'){
		if($this->_showLabel()){
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
	 