<?php
class Codazon_Themeframework_Block_Widget_Categoriesmenu extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{
	protected $_templateFile;
	protected $_menu;
	const CACHE_TAG = 'CODAZON_THEMEFRAMEWORK';
	
	public function _construct(){
		$this->_urlModel = Mage::getSingleton('core/url_rewrite')->getResource();
		$this->_catModel = Mage::getModel('catalog/category');
		$this->setCacheTags(array(self::CACHE_TAG));
        $this->setCacheLifetime(86400);
	}
	
	public function getCategoryUrlById($id){
		$requestPath = $this->_urlModel->getRequestPathByIdPath('category/' . $id, Mage::app()->getStore()->getId());
		if(!empty($requestPath)){
			return Mage::getBaseUrl() . $requestPath;
		} else {
			return $this->_catModel->load($id)->getUrl();
		}
	}
	protected function _toHtml(){
		$default = array_replace( array(
			'id_path' => 'category/2',
			'template' => 'codazon_themeframework/widget/categoriesmenu.phtml',
			'children_wrap_class' => 'cdz-sub-cat',
			'has_wrap' => true,
			'parent_wrap_class' => 'cdz-nav-wrap'
		), $this->getData());
		$this->setData($default);
		
		$menuTree = $this->getMenuTree();
        $childrenWrapClass = $this->getChildrenWrapClass();
		if(is_null($this->getTemplate())){
			$this->setTemplate($this->getData('template'));
		}
		if (!$this->getData('template') || is_null($menuTree) || is_null($childrenWrapClass)) {
            throw new Exception("Top-menu renderer isn't fully configured.");
        }

        $includeFilePath = realpath(Mage::getBaseDir('design') . DS . $this->getTemplateFile());
        if (strpos($includeFilePath, realpath(Mage::getBaseDir('design'))) === 0) {
            $this->_templateFile = $includeFilePath;
        } else {
            throw new Exception('Not valid template file:' . $this->_templateFile);
        }
        $html = $this->render($menuTree, $childrenWrapClass,$this->getHasWrap(),$this->getParentWrapClass());
		return $html;
	}
	protected function getMenuTree(){
		$parentId = explode('/', $this->getData('id_path'));
		$parentId = $parentId[1];
		if($this->hasStoreId()) {
			$store = Mage::app()->getStore($this->getStoreId());
		} else {
			$store = Mage::app()->getStore();
		}
		$storeId = $store->getId();
		$tree = Mage::getResourceSingleton('catalog/category_tree')->load();
		$parent = $tree->getNodeById($parentId);
		
		$collection = $this->_catModel->getCollection()
			->setStoreId($storeId)
			->addAttributeToFilter('is_active',1)
			->addAttributeToFilter('include_in_menu',1)
			->addAttributeToSelect('name')
			->addAttributeToSelect('is_active');
		
		$tree->addCollectionData($collection, true);
		return  $parent;
	}
	
	public function render(Varien_Data_Tree_Node $menuTree, $childrenWrapClass = '', $hasWrap = false, $parentWrapClass = 'nav-wrap', $options=null)
    {
        ob_start();
       	$html = include $this->_templateFile;
        $directOutput = ob_get_clean();

        if (is_string($html)) {
            return $html;
        } else {
            return $directOutput;
        }
    }
	
	protected function _getRenderedMenuItemAttributes(Varien_Data_Tree_Node $item)
    {
        $html = '';
        $attributes = $this->_getMenuItemAttributes($item);

        foreach ($attributes as $attributeName => $attributeValue) {
            $html .= ' ' . $attributeName . '="' . str_replace('"', '\"', $attributeValue) . '"';
        }

        return $html;
    }

	protected function _getMenuItemAttributes(Varien_Data_Tree_Node $item)
    {
        $menuItemClasses = $this->_getMenuItemClasses($item);
        $attributes = array(
            'class' => implode(' ', $menuItemClasses)
        );

        return $attributes;
    }
	protected function _getMenuItemClasses(Varien_Data_Tree_Node $item)
    {
        $classes = array();

        $classes[] = 'level' . $item->getLevel();
        $classes[] = $item->getPositionClass();

        if ($item->getIsFirst()) {
            $classes[] = 'first';
        }

        if ($item->getIsLast()) {
            $classes[] = 'last';
        }

        if ($item->getClass()) {
            $classes[] = $item->getClass();
        }

        if ($item->hasChildren()) {
            $classes[] = 'parent';
        }

        return $classes;
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
        $shortCacheId = array(
            'CODAZON_CATEGORIESMENU',
            Mage::app()->getStore()->getId(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->getCustomerGroupId(),
            'template' => $this->getTemplate(),
            'name' => $this->getNameInLayout(),
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