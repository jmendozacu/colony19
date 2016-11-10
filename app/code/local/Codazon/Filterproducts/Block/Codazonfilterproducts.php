<?php
class Codazon_Filterproducts_Block_Codazonfilterproducts
    extends Mage_Catalog_Block_Product_List
    implements Mage_Widget_Block_Interface
{
	protected $_dataArray;
	/**
     * @var Mage_Core_Model_Session
     */
	protected $_coreSession;
	/**
     * @var Mage_Log_Model_Visitor
     */
	protected $_logVisitor;
	/**
     * @var Mage_Catalog_Model_Product_Visibility
     */
	protected $_productVisibility;
	protected $_coreHelper;
	public function getDataArray(){
		return $this->_dataArray;	
	}
	protected function _construct()
	{
		$this->_coreHelper = $this->helper('core/url');
		$this->_coreSession = Mage::getSingleton('core/session');
		$this->_productVisibility = Mage::getSingleton('catalog/product_visibility');
		$this->_logVisitor = Mage::getSingleton('log/visitor');
		
		if($this->getData('filtertype') != 2){
			if(!$this->getData('cache_lifetime')){
				$this->setData('cache_lifetime',1);
			}
			$this->addData(array(
				'cache_tags'     => array('CODAZON_FILTER'),
				'cache_key'      => 'codazon_filterproducts_'.
					implode('|',array(
						Mage::app()->getStore()->getId(),
						(int)Mage::app()->getStore()->isCurrentlySecure(),
						Mage::app()->getStore()->getCurrentCurrencyCode(),
						Mage::getSingleton('customer/session')->getCustomerGroupId(),
						md5(json_encode($this->getData()))
					))
			));
		}else{
			$this->setData('cache_lifetime',1);
		}

		$this->addPriceBlockType('bundle', 'bundle/catalog_product_price', 'bundle/catalog/product/price.phtml');
	}
	public function display($element){
		if( in_array($element,$this->_dataArray['display']) ){
			return true;
		}else{
			return false;
		}
	}
	protected function _toHtml(){
		$this->_dataArray = array_replace( array(
			'categories'	=> array(),
			'filtertype' 	=> '',
			'attribute'		=> '',
			'orderby'		=> 'price',
			'order'			=> 'asc',
			'limit'			=> 12,
			'use_ajax'		=> 0,
			'custom_class'	=> '',
			'custom_template'=>'codazon_filterproducts/grid.phtml',
			'display'		=> 'name,image,price,description,addtocart_btn,wishlist_btn,compare_btn',
			'thumb_width'	=> 250,
			'thumb_height'	=> 250,
			'columns_count'	=> 1,
			'use_slider'	=> 0,
			'auto_width'	=> 0,
			'margin'		=> 0,
			'items_1280'	=> 4,
			'items_1024'	=> 3,
			'items_768'		=> 3,
			'items_480'		=> 3,
			'items_320'		=> 2,
			'items_0'		=> 1,
			'auto_play'		=> 0,
			'show_dots'		=> 0,
			'show_nav'		=> 1,
			'lazy_load'		=> 0,
			'loop'			=> 0,
			'cur_page'		=> 1
		),$this->getData() );
		
		$data = $this->_dataArray;
		
		if( $this->getData('use_ajax') != 1){
			$this->_dataArray['display'] = explode(',',$data['display']);
		}
		if($data['use_ajax'] == 1){
			$this->_dataArray['block_url'] = $this->_coreHelper->getCurrentUrl();
			$this->setData('block_url',$this->_coreHelper->getCurrentUrl());
			$this->setTemplate('codazon_filterproducts/ajax.phtml');	
			$html = parent::_toHtml();
			return $html;
		}
		
		if($data['categories'] != array()){
			$data['categories'] = array_unique(explode(',',trim($data['categories'])));
		}
		if($data['filtertype'] != 3){
			$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')->addStoreFilter();
		}else{
			$collection = Mage::getResourceModel('reports/product_collection')->addAttributeToSelect('*')->addStoreFilter();
		}
		$collection = $this->_addProductAttributesAndPrices($collection)->addStoreFilter()->addAttributeToFilter('status', array('neq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED))->addAttributeToFilter('visibility', array("neq" => 1));
		
		switch($data['filtertype']){
			case 1:	//new
				$collection = $this->getNewProducts($collection);
				$collection->setOrder($data['orderby'], $data['order']);
				break;
			case 2:	//best seller
				$collection = $this->getBestSellerProducts($collection);
				break;
			case 3: //most viewed
				$collection = $this->getMostViewProducts($collection);
				break;
			case 4:	//attribute
				$collection = $this->getProductsByAttribute($collection, $data['attribute']);
				break;
			case 0:
			default:
				break;
		}
		
		$this->addCategoriestoFilter($collection,$data['categories']);
		$collection->addAttributeToSort($data['orderby'], $data['order']);	
		
		$collection->setPageSize($data['limit']);
		$collection->setCurPage($data['cur_page']);
		$collection->getSelect()->group('e.entity_id');
	
		
		$this->setProductCollection($collection);
		//return $collection->getSelect();
		$this->setTemplate($data['custom_template']);
		$html = parent::_toHtml();
		return $html;
    }
	
	public function addCategoriestoFilter(&$collection,array $categories = null){
		$resource = Mage::getSingleton('catalog/resource_eav_mysql4_product_collection');
		if(count($categories) > 0){
			$collection->joinField('category_id',
				'catalog/category_product',
				'category_id',
				'product_id = entity_id',
				NULL,
				'left'
			);
			if($resource->isEnabledFlat()){
			    $collection->getSelect()->where('at_category_id.category_id in ('.implode(',',$categories).')');
			}else{
			    $collection->addAttributeToFilter('category_id', array('in' => $categories ));
			}
		}
	}
	public function getNewProducts($collection){
		$todayDate  = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
		$collection = $this->_addProductAttributesAndPrices($collection)
			->addStoreFilter()
			->addAttributeToFilter('news_from_date', array('date' => true, 'to' => $todayDate))
			->addAttributeToFilter('news_to_date', array('or'=> array(
				0 => array('date' => true, 'from' => $todayDate),
				1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter(array(
				array('attribute' => 'news_from_date', 'is' => new Zend_Db_Expr('not null')),
				array('attribute' => 'news_to_date', 'is' => new Zend_Db_Expr('not null'))
			))
         	->addAttributeToSort('news_from_date', 'desc');
		return $collection;
	}
	public function getMostViewProducts($collection){
		$collection->addOrderedQty()->addViewsCount();
		return $collection; 
	}
	protected function _bestsellerProductArray()
    {
		$coreResource = Mage::getSingleton('core/resource');
        $strCategories = $this->getData('categories');
		if(is_array( $strCategories )){
			$strCategories = implode(',',$strCategories);	
		}
        if ($strCategories) {
            $query = "  
						SELECT DISTINCT SUM( order_items.qty_ordered ) AS  `ordered_qty` ,  `order_items`.`name` AS  `order_items_name` ,  `order_items`.`product_id` AS  `entity_id` ,  `e`.`entity_type_id` ,  `e`.`attribute_set_id` , `e`.`type_id` ,  `e`.`sku` ,  `e`.`has_options` ,  `e`.`required_options` ,  `e`.`created_at` ,  `e`.`updated_at` 
						FROM  `" . $coreResource->getTableName('sales_flat_order_item') .
                "` AS  `order_items` 
						INNER JOIN  `" . $coreResource->getTableName('sales_flat_order') .
                "` AS  `order` ON  `order`.entity_id = order_items.order_id
						AND  `order`.state <>  'canceled'
						LEFT JOIN  `" . $coreResource->getTableName('catalog_product_entity') .
                "` AS  `e` ON e.entity_id = order_items.product_id
						INNER JOIN  `" . $coreResource->getTableName('catalog_product_website') .
                "` AS  `product_website` ON product_website.product_id = e.entity_id
						AND product_website.website_id =  '" . Mage::app()->getWebsite()->getId() .
                "'
						INNER JOIN  `" . $coreResource->getTableName('catalog_category_product_index') .
                "` AS  `cat_index` ON cat_index.product_id = e.entity_id
						AND cat_index.store_id = '" . Mage::app()->getStore()->getStoreId() . "'
						AND cat_index.category_id
						IN ( " . $strCategories . " ) 
						WHERE (
						parent_item_id IS NULL
						)
						GROUP BY  `order_items`.`product_id` 
						HAVING (
						SUM( order_items.qty_ordered ) >0
						)
						ORDER BY  `ordered_qty` DESC 
						LIMIT 0 ," . $this->getLimit() . "
					";
        } else {
            $query = "	SELECT SUM( order_items.qty_ordered ) AS  `ordered_qty` ,  `order_items`.`name` AS  `order_items_name` ,  `order_items`.`product_id` AS  `entity_id` ,  `e`.`entity_type_id` ,  `e`.`attribute_set_id` , `e`.`type_id` ,  `e`.`sku` ,  `e`.`has_options` ,  `e`.`required_options` ,  `e`.`created_at` ,  `e`.`updated_at` 
						FROM  `" . $coreResource->getTableName('sales_flat_order_item') .
                "` AS  `order_items` 
						INNER JOIN  `" . $coreResource->getTableName('sales_flat_order') .
                "` AS  `order` ON  `order`.entity_id = order_items.order_id
						AND  `order`.state <>  'canceled'
						LEFT JOIN  `" . $coreResource->getTableName('catalog_product_entity') .
                "` AS  `e` ON e.entity_id = order_items.product_id
						INNER JOIN  `" . $coreResource->getTableName('catalog_product_website') .
                "` AS  `product_website` ON product_website.product_id = e.entity_id
						AND product_website.website_id =  '" . Mage::app()->getWebsite()->getId() .
                "'
						WHERE (
							parent_item_id IS NULL
						)
						GROUP BY  `order_items`.`product_id` 
						HAVING (
							SUM( order_items.qty_ordered ) > 0
						)
							ORDER BY  `ordered_qty` DESC 
							LIMIT 0 ," . $this->getLimit() . "
						";
        }
        $readConnection = $coreResource->getConnection('core_read');
        return $readConnection->fetchAll($query);
    }
	
	
	public function getBestSellerProducts($collection){
		$_bestsellerProducts = $this->_bestsellerProductArray();
		$_tempProductIds = array();
		$count = 0;
        $limit = $this->getData('limit');
		$orderString = array('CASE e.entity_id');
		foreach ($_bestsellerProducts as $i => $_product) {
            if (in_array($_product['entity_id'], $_tempProductIds))
                continue;
            else {
                $_tempProductIds[] = $_product['entity_id'];
                $orderString[] = 'WHEN ' . $_product['entity_id'] . ' THEN ' . $i;
                $count++;
                if ($count == $limit)
                    break;
            }
        }
		$orderString[] = 'END';
		$orderString = implode(' ', $orderString);

        $collection->addAttributeToFilter('entity_id', array('in' => $_tempProductIds))->
            addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
				
        return $collection;
	}
	public function getProductsByAttribute($collection, $attribute){
        $collection->addAttributeToFilter($attribute, array('gt' => 0));
		return $collection;
	}
	public function getProductGalleryHtml($product, $template = 'codazon_filterproducts/view/media.phtml'){
		return $this->getLayout()->createBlock('catalog/product_view_media')->setProduct($product)->setTemplate($template)->toHtml();
	}
	
	public function getAddToCartUrl($product, $additional = array()){
		return parent::getAddToCartUrl($product,$additional)."?options=cart";
	}
	protected function _getUrlParams($product)
    {
        return array(
            'product' => $product->getId(),
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => $this->_coreHelper->getEncodedUrl($this->getBlockUrl()),
            Mage_Core_Model_Url::FORM_KEY => $this->_coreSession->getFormKey()
        );
    }
	public function getAddToCompareUrl($product)
    {
		$url = $this->getBlockUrl();
		if($url){
			$product->setData('block_url',$url);
		}
        return $this->helper('filterproducts/compare')->getAddUrl($product,$url);
		//return $this->getUrl('catalog/product_compare/add', $this->_getUrlParams($product));
    }
}
