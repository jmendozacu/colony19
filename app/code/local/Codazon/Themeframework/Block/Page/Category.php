<?php
class Codazon_Themeframework_Block_Page_Category extends Mage_Core_Block_Template
{
	protected $_catId;
	protected $_catModel;
	const PAGE_VAR_NAME = 'p';
	const DEFAULT_LIMIT = 12;
	const CACHE_TAG = 'CODAZON_THEMEFRAMEWORK';
	protected $_currentEntityKey;
	
	public function _construct(){
		$this->_catModel = Mage::getModel('catalog/category');
		parent::_construct();
		$this->setCacheTags(array(self::CACHE_TAG));
        $this->setCacheLifetime(86400);
	}
	public function getCatId(){
		if(!$this->_catId){
			$this->_catId = $this->getRequest()->getParam('id');
		}
		return $this->_catId;
	}
	public function getCurrentCategory(){
		if (!$this->hasData('current_category')) {
            $this->setData('current_category', Mage::registry('current_category'));
        }
        return $this->getData('current_category');	
	}
	public function getChildren(){
		if (!$this->hasData('children')) {
			$limit = $this->getRequest()->getParam('limit');
			$limit = $limit?$limit:self::DEFAULT_LIMIT;
			$page = $this->getRequest()->getParam(self::PAGE_VAR_NAME);
			$page = $page?$page:1;
			$id = $this->getCatId();
			$children = Mage::getResourceModel('catalog/category_collection');
			$children->getSelect()->where("parent_id = $id");
			$children->addAttributeToSelect('e.entity_id')->setPageSize($limit)->setCurpage($page);
			$this->setData('children',$children);
		}
		return $this->getData('children');
	}
	public function getCollectionById($id){
		return $this->_catModel->setStoreId(Mage::app()->getStore()->getId())->load($id);
	}
	public function getCurrentEntityKey()
    {
        if (null === $this->_currentEntityKey) {
            $this->_currentEntityKey = Mage::registry('current_entity_key')
                ? Mage::registry('current_entity_key') : Mage::app()->getStore()->getRootCategoryId();
        }
        return $this->_currentEntityKey;
    }
	public function getCacheKeyInfo()
    {
		$data = $this->getRequest()->getParams();
        $shortCacheId = array(
            'CODAZON_CATEGORIES',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
			'page' => $data['p'],
			'limit' => $data['limit'],
            $this->getCurrentEntityKey()
        );
        $cacheId = $shortCacheId;

        $shortCacheId = array_values($shortCacheId);
        $shortCacheId = implode('|', $shortCacheId);
        $shortCacheId = md5($shortCacheId);

        $cacheId['entity_key'] = $this->getCurrentEntityKey();
        $cacheId['short_cache_id'] = $shortCacheId;

        return $cacheId;
    }
}
?>