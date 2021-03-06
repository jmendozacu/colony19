<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Catalogpermissions
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Catalogpermissions_Helper_Connection extends Mage_Core_Helper_Abstract
{
    const CP_ADD_DISABLED_ATTR_TO_FILTER_COLLECTION_FLAG = 'cp_add_disabled_attr_to_filter_collection_flag';

    /**
     * @var Mage_Core_Model_Resource
     */
    protected $_resource = null;

    /**
     * @var Varien_Db_Adapter_Pdo_Mysql
     */
    protected $_connection = null;

    /**
     * Session cache by store_id for inactive categories
     *
     * @var array
     */
    private $inactiveCategoriesCache = array();

    public static $_lock = false;


    public function getConnection()
    {
        return $this->_initResourceConnection();
    }

    public function getSelect($reset = true)
    {
        $select = $this->_initResourceConnection()->select();
        if ($reset) {
            $select->reset();
        }
        return $select;
    }

    protected function _initResourceConnection()
    {
        if (!is_null($this->_resource) && !is_null($this->_connection)) {
            return $this->_connection;
        }

        $this->_resource = Mage::getSingleton('core/resource');

        return $this->_initConnection();
    }

    private function _initConnection()
    {
        $this->_connection = $this->_resource->getConnection('core_read');
        return $this->_connection;
    }

    public function getTable($name)
    {
        return $this->_resource->getTableName($name);
    }

    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    public function isDisabled($productId)
    {
        $productIds = array($productId);
        $collection = $this->_getProcessedProductCollection($productIds);

        if (!count($collection)) {
            return true;
        } else {
            return false;
        }
    }

    public function removeDisabledProducts(array $productIds)
    {
        $filterdIds = array();
        $collection = $this->_getProcessedProductCollection($productIds);

        if ($collection->getSize() > 0) {
            $filterdIds = $collection->getAllIds();
        }
        return $filterdIds;
    }

    protected function _getProcessedProductCollection(array $productIds)
    {
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $keyField = 'entity_id';
        $attrCode = $eavAttribute->getIdByCode(
            'catalog_product', AW_Catalogpermissions_Helper_Data::CP_DISABLE_PRODUCT
        );
        $attT = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_text";
        /** @var AW_Catalogpermissions_Model_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();
        $cn = $this->_getCorrelationName($collection);
        $collection->getSelect()
            ->joinLeft(
                array('_cp_def' => $attT),
                "_cp_def.entity_id = {$cn}.{$keyField} AND _cp_def.attribute_id = {$attrCode} AND _cp_def.store_id = 0",
                array()
            )
            ->joinLeft(
                array('_catalogpermissions_store' => $attT),
                "_catalogpermissions_store.entity_id = {$cn}.{$keyField} AND _catalogpermissions_store.attribute_id = {$attrCode} AND _catalogpermissions_store.store_id = {$this->getStoreId(
                )}", array()
            )
            ->where("{$cn}.{$keyField} IN (?)", $productIds);

        $where =
            "IF(_catalogpermissions_store.value_id > 0, _catalogpermissions_store.value, _cp_def.value) NOT REGEXP '(^|,)"
            . AW_Catalogpermissions_Helper_Data::getCustomerGroup()
            . "(,|$)' "
            . " OR (_catalogpermissions_store.value IS NULL AND _cp_def.value IS NULL) "
            . " OR (_catalogpermissions_store.value IS NULL AND _catalogpermissions_store.value_id IS NOT NULL)";
        $orWhere = null;

        if (Mage::helper('catalogpermissions')->isMssEnabled()) {
            $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_int";
            $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
            $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');

            $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
            $attrCodeMss = $eavAttribute->getIdByCode(
                'catalog_product',
                AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRODUCT
            );

            $collection
                ->getSelect()
                ->joinLeft(
                    array('_cp_mss_rule' => $attMssTable),
                    "_cp_mss_rule.entity_id = {$cn}.{$keyField} "
                    . " AND _cp_mss_rule.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule.store_id = {$this->getStoreId()}",
                    array()
                )
                ->joinLeft(
                    array('_mss_index' => $mssIndexTable),
                    "_mss_index.rule_id = _cp_mss_rule.value"
                    ." AND _mss_index.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule' => $mssRulesTable),
                    "_mss_rule.filter_id = _cp_mss_rule.value"
                    ." AND _mss_rule.is_active = '1'",
                    array()
                )
                ->joinLeft(
                    array('_cp_mss_rule_def' => $attMssTable),
                    "_cp_mss_rule_def.entity_id = {$cn}.{$keyField} "
                    . " AND _cp_mss_rule_def.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_mss_index_def' => $mssIndexTable),
                    "_mss_index_def.rule_id = _cp_mss_rule_def.value"
                    ." AND _mss_index_def.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule_def' => $mssRulesTable),
                    "_mss_rule_def.filter_id = _cp_mss_rule_def.value"
                    ." AND _mss_rule_def.is_active = 1",
                    array()
                )
            ;

            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) = 1";
                $andWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( {$where} OR {$orWhere} ) AND ( {$andWhere} ) ";
            }
            else {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( " . $where . " ) OR ( " . $orWhere . " ) ";
            }
        }
        $collection->getSelect()->where($where);

        return $collection;
    }

    /**
     * Add to product collection catalog permission limitation
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @param string                                                    $keyField
     * @param array                                                     $additional
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addDisabledAttrToFilter($collection, $keyField = 'entity_id', array $additional = array())
    {
        /**
         *
         * Product collection load invokes in Mage_Sales_Model_Mysql4_Quote_Item_Collection _afterLoad -> _assignProducts method
         * but we don't delete products which already in cart
         *
         */
        if (get_class($collection) == 'AW_Catalogpermissions_Model_Product_Collection') {
            if (self::$_lock === true) {
                self::$_lock = false;
                return $collection;
            }
        }
        if ($this->_alreadyProcessed($collection)
            || $collection->getFlag(self::CP_ADD_DISABLED_ATTR_TO_FILTER_COLLECTION_FLAG)
        ) {
            return $collection;
        }

        $cn = $this->_getCorrelationName($collection);
        $this->_initResourceConnection();

        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();

        $attrCode = $eavAttribute->getIdByCode(
            'catalog_product', AW_Catalogpermissions_Helper_Data::CP_DISABLE_PRODUCT
        );
        $attT = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_text";

        $collection->getSelect()
            ->joinLeft(
                array('_cp_def' => $attT),
                "_cp_def.entity_id = {$cn}.{$keyField} AND _cp_def.attribute_id = {$attrCode} AND _cp_def.store_id = 0",
                array()
            )
            ->joinLeft(
                array('_catalogpermissions_store' => $attT),
                "_catalogpermissions_store.entity_id = {$cn}.{$keyField} AND _catalogpermissions_store.attribute_id = {$attrCode} AND _catalogpermissions_store.store_id = {$this->getStoreId(
                )}", array()
            )
        ;

        $where =
            "IF(_catalogpermissions_store.value_id > 0, _catalogpermissions_store.value, _cp_def.value) NOT REGEXP '(^|,)"
                . AW_Catalogpermissions_Helper_Data::getCustomerGroup()
                . "(,|$)' "
            . " OR (_catalogpermissions_store.value IS NULL AND _cp_def.value IS NULL) "
            . " OR (_catalogpermissions_store.value IS NULL AND _catalogpermissions_store.value_id IS NOT NULL)";
        $orWhere = null;

        if (Mage::helper('catalogpermissions')->isMssEnabled()) {
            $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_int";
            $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
            $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');
            $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
            $attrCodeMss = $eavAttribute->getIdByCode(
                'catalog_product',
                AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRODUCT
            );

            $collection
                ->getSelect()
                ->joinLeft(
                    array('_cp_mss_rule' => $attMssTable),
                    "_cp_mss_rule.entity_id = {$cn}.{$keyField} "
                    . " AND _cp_mss_rule.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule.store_id = {$this->getStoreId()}",
                    array()
                )
                ->joinLeft(
                    array('_mss_index' => $mssIndexTable),
                    "_mss_index.rule_id = _cp_mss_rule.value"
                    ." AND _mss_index.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule' => $mssRulesTable),
                    "_mss_rule.filter_id = _cp_mss_rule.value"
                    ." AND _mss_rule.is_active = '1'",
                    array()
                )
                ->joinLeft(
                    array('_cp_mss_rule_def' => $attMssTable),
                    "_cp_mss_rule_def.entity_id = {$cn}.{$keyField} "
                    . " AND _cp_mss_rule_def.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_mss_index_def' => $mssIndexTable),
                    "_mss_index_def.rule_id = _cp_mss_rule_def.value"
                    ." AND _mss_index_def.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule_def' => $mssRulesTable),
                    "_mss_rule_def.filter_id = _cp_mss_rule_def.value"
                    ." AND _mss_rule_def.is_active = '1'",
                    array()
                )
            ;

            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) = 1";
                $andWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( {$where} OR {$orWhere} ) AND ( {$andWhere} ) ";
            }
            else {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( " . $where . " ) OR ( " . $orWhere . " ) ";
            }
        }
        $collection->getSelect()->where($where);

        if (
            Mage::app()->getRequest()->getModuleName() == 'rss'
            || get_class($collection) == 'Mage_Bundle_Model_Mysql4_Selection_Collection'
            || get_class($collection) == 'Mage_Bundle_Model_Resource_Selection_Collection'
            || (
                AW_Catalogpermissions_Helper_Data::_isSortByPrice()
                && !isset($additional['noPriceFilter'])
            )
        ) {
            $priceAttr = $eavAttribute->getIdByCode(
                'catalog_product', AW_Catalogpermissions_Helper_Data::CP_DISABLE_PRICE
            );
            $collection->getSelect()
                ->joinLeft(
                    array('_cp_price_def' => $attT),
                    "_cp_price_def.entity_id = {$cn}.{$keyField} AND _cp_price_def.attribute_id = {$priceAttr} AND _cp_price_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_cp_price_store' => $attT),
                    "_cp_price_store.entity_id = {$cn}.{$keyField} AND _cp_price_store.attribute_id = {$priceAttr} AND _cp_price_store.store_id = {$this->getStoreId(
                    )}", array()
                )
            ;

            $wherePrice =
                "IF(_cp_price_store.value_id > 0, _cp_price_store.value, _cp_price_def.value) NOT REGEXP '(^|,)"
                . AW_Catalogpermissions_Helper_Data::getCustomerGroup()
                . "(,|$)' OR ( _cp_price_store.value IS NULL AND  _cp_price_def.value IS NULL)";
            $orWherePrice = null;

            if (Mage::helper('catalogpermissions')->isMssEnabled()) {
                $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_int";
                $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
                $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');

                $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
                $attrCodeMssPrice = $eavAttribute->getIdByCode(
                    'catalog_product',
                    AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRICE
                );

                $collection
                    ->getSelect()
                    ->joinLeft(
                        array('_cp_mss_price_rule' => $attMssTable),
                        "_cp_mss_price_rule.entity_id = {$cn}.{$keyField} "
                        . " AND _cp_mss_price_rule.attribute_id = {$attrCodeMssPrice} "
                        . " AND _cp_mss_price_rule.store_id = {$this->getStoreId()}",
                        array()
                    )
                    ->joinLeft(
                        array('_mss_price_index' => $mssIndexTable),
                        "_mss_price_index.rule_id = _cp_mss_price_rule.value"
                        ." AND _mss_price_index.customer_id = '{$customerId}'",
                        array()
                    )
                    ->joinLeft(
                        array('_mss_price_rule' => $mssRulesTable),
                        "_mss_price_rule.filter_id = _cp_mss_price_rule.value"
                        ." AND _mss_price_rule.is_active = '1'",
                        array()
                    )
                    ->joinLeft(
                        array('_cp_mss_price_rule_def' => $attMssTable),
                        "_cp_mss_price_rule_def.entity_id = {$cn}.{$keyField} "
                        . " AND _cp_mss_price_rule_def.attribute_id = {$attrCodeMssPrice} "
                        . " AND _cp_mss_price_rule_def.store_id = 0",
                        array()
                    )
                    ->joinLeft(
                        array('_mss_price_index_def' => $mssIndexTable),
                        "_mss_price_index_def.rule_id = _cp_mss_price_rule_def.value"
                        ." AND _mss_price_index_def.customer_id = '{$customerId}'",
                        array()
                    )
                    ->joinLeft(
                        array('_mss_price_rule_def' => $mssRulesTable),
                        "_mss_price_rule_def.filter_id = _cp_mss_price_rule_def.value"
                        ." AND _mss_price_rule_def.is_active = 1",
                        array()
                    )
                ;


                if (
                    AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
                ) {
                    $orWherePrice =
                        "IF(_cp_mss_price_rule.value_id > 0, _mss_price_rule.is_active, _mss_price_rule_def.is_active) = 1";
                    $andWherePrice =
                        "IF(_cp_mss_price_rule.value_id > 0, _mss_price_index.customer_id, _mss_price_index_def.customer_id) IS NULL"
                        . " OR (_mss_price_rule.is_active IS NULL AND _mss_price_rule_def.is_active IS NULL) "
                        . " OR (_mss_price_rule.is_active IS NULL AND _cp_mss_price_rule.value_id IS NOT NULL) "
                    ;
                    $wherePrice = " ( {$wherePrice} OR {$orWherePrice} ) AND ( {$andWherePrice} ) ";
                }
                else {
                    $orWherePrice =
                        "IF(_cp_mss_price_rule.value_id > 0, _mss_price_index.customer_id, _mss_price_index_def.customer_id) IS NULL"
                        . " OR (_mss_price_rule.is_active IS NULL AND _mss_price_rule_def.is_active IS NULL) "
                        . " OR (_mss_price_rule.is_active IS NULL AND _cp_mss_price_rule.value_id IS NOT NULL) "
                    ;
                    $wherePrice = " ( " . $wherePrice . " ) OR ( " . $orWherePrice . " ) ";
                }
            }
            $collection->getSelect()->where($wherePrice);
        }

        /* Get disabled categories */
        $disabledCategories = Mage::registry(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE);
        if (is_null($disabledCategories)) {
            $this->cacheDisabledCategories();
            $disabledCategories = Mage::registry(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE);
        }

        if (!empty($disabledCategories)) {
            $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
            /**
             * Limit selected products by disabled categories in case when product collection using category filter
             * Root category used for search. Check for product availability at least in one category for customer.
             * Use short way to limit products for not root categories.
             */
            if (isset($fromPart['cat_index'])) {
                $category_product_table = Mage::getSingleton('core/resource')->getTableName('catalog/category_product');
                $category_table = Mage::getSingleton('core/resource')->getTableName('catalog/category');
                $subCategoriesAttr = Mage::getSingleton('eav/entity_attribute')->getIdByCode(
                    'catalog_category', 'all_children'
                );
                $not_active_categories = $this->getNotActiveCategories();
                $exclude_categories = array_unique(array_merge($disabledCategories, $not_active_categories));
                // Product can be selected in collection if it is at least in one visible category
                // Subquery select set of child subcategories of cat_index.category_id exclude disabled and not active categories
                $sub_select2 = 'SELECT cet.entity_id FROM ' . $category_table
                    . ' as cet WHERE cet.path REGEXP CONCAT("(^|/)",cat_index.category_id,"(/|$)") AND cet.entity_id NOT IN ('
                    . implode(',', $exclude_categories) . ')';
                // External subquery part select at least one category allowed for product
                $sub_select1
                    = 'SELECT cpt.category_id FROM ' . $category_product_table . ' as cpt WHERE cpt.category_id IN ('
                    . $sub_select2 . ') AND cpt.product_id=e.entity_id';
                // Subquery for products without category
                $sub_select3 = 'SELECT cpt.category_id FROM ' . $category_product_table
                    . ' as cpt WHERE cpt.product_id=e.entity_id';
                $collection->getSelect()->where('EXISTS (' . $sub_select1 . ') OR NOT EXISTS (' . $sub_select3 . ')');
            }
        }
        $collection->setFlag(self::CP_ADD_DISABLED_ATTR_TO_FILTER_COLLECTION_FLAG, true);
        return $collection;
    }

    public function cacheDisabledPriceProducts($_storeId = null, $_customerGroup = null)
    {
        if (is_null($_storeId)) {
            $_storeId = $this->getStoreId();
        }
        if (is_null($_customerGroup)) {
            $_customerGroup = AW_Catalogpermissions_Helper_Data::getCustomerGroup();
        }

        if (AW_Catalogpermissions_Model_Observer::$useCache === true) {
            $_cacheTag = AW_Catalogpermissions_Model_Cache::getCacheTag(
                $_customerGroup, $_storeId, AW_Catalogpermissions_Model_Cache::PRODUCT_CACHE_TAG
            );
            $cacheData = AW_Catalogpermissions_Model_Cache::loadCache($_cacheTag);
            $cacheData = @unserialize($cacheData);
            if (is_array($cacheData)) {
                Mage::register(AW_Catalogpermissions_Helper_Data::DISABLED_PRICE_PROD_SCOPE, $cacheData, true);
                return;
            }
        }

        $this->_initResourceConnection();
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();

        $attrCode = $eavAttribute->getIdByCode('catalog_product', AW_Catalogpermissions_Helper_Data::CP_DISABLE_PRICE);
        $productEntityTable = $this->getTable('catalog/product');
        $attT = $this->getTable('catalog/product') . "_text";

        $select = $this->getSelect()
            ->from(array('e' => $productEntityTable), array('e.entity_id'))
            ->joinLeft(
                array('_cp_def' => $attT),
                "_cp_def.entity_id = e.entity_id AND _cp_def.attribute_id = {$attrCode} AND _cp_def.store_id = 0",
                array()
            )
            ->joinLeft(
                array('_catalogpermissions_store' => $attT),
                "_catalogpermissions_store.entity_id = e.entity_id AND _catalogpermissions_store.attribute_id = {$attrCode} AND _catalogpermissions_store.store_id = {$_storeId}",
                array()
            )
        ;
        $where =
            "IF(_catalogpermissions_store.value_id > 0, _catalogpermissions_store.value, _cp_def.value) REGEXP '(^|,){$_customerGroup}(,|$)'"
        ;

        if (Mage::helper('catalogpermissions')->isMssEnabled()) {
            $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/product') . "_int";
            $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
            $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');

            $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
            $attrCodeMssPrice = $eavAttribute->getIdByCode(
                'catalog_product',
                AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRICE
            );

            $select
                ->joinLeft(
                    array('_cp_mss_price_rule' => $attMssTable),
                    "_cp_mss_price_rule.entity_id = e.entity_id "
                    . " AND _cp_mss_price_rule.attribute_id = {$attrCodeMssPrice} "
                    . " AND _cp_mss_price_rule.store_id = {$this->getStoreId()}",
                    array()
                )
                ->joinLeft(
                    array('_mss_price_index' => $mssIndexTable),
                    "_mss_price_index.rule_id = _cp_mss_price_rule.value"
                    ." AND _mss_price_index.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_price_rule' => $mssRulesTable),
                    "_mss_price_rule.filter_id = _cp_mss_price_rule.value"
                    ." AND _mss_price_rule.is_active = '1'",
                    array()
                )
                ->joinLeft(
                    array('_cp_mss_price_rule_def' => $attMssTable),
                    "_cp_mss_price_rule_def.entity_id = e.entity_id "
                    . " AND _cp_mss_price_rule_def.attribute_id = {$attrCodeMssPrice} "
                    . " AND _cp_mss_price_rule_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_mss_price_index_def' => $mssIndexTable),
                    "_mss_price_index_def.rule_id = _cp_mss_price_rule_def.value"
                    ." AND _mss_price_index_def.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_price_rule_def' => $mssRulesTable),
                    "_mss_price_rule_def.filter_id = _cp_mss_price_rule_def.value"
                    ." AND _mss_price_rule_def.is_active = 1",
                    array()
                )
            ;

            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $andWhere =
                    " IF(_cp_mss_price_rule.value_id > 0, _mss_price_rule.is_active, _mss_price_rule_def.is_active) IS NULL "
                ;
                $orWhere =
                    "IF(_cp_mss_price_rule.value_id > 0, _mss_price_index.customer_id, _mss_price_index_def.customer_id) = '{$customerId}'"
                    . " AND IF(_cp_mss_price_rule.value_id > 0, _mss_price_rule.is_active, _mss_price_rule_def.is_active) = 1"
                ;
                $where = " ( ( {$where} ) AND ( {$andWhere} ) OR ( {$orWhere} ) ) ";
            }
            else {
                $andWhere =
                    "IF(_cp_mss_price_rule.value_id > 0, _mss_price_index.customer_id, _mss_price_index_def.customer_id) = '{$customerId}'"
                    . " AND IF(_cp_mss_price_rule.value_id > 0, _mss_price_rule.is_active, _mss_price_rule_def.is_active) = 1"
                    . " OR IF(_cp_mss_price_rule.value_id > 0, _mss_price_rule.is_active, _mss_price_rule_def.is_active) IS NULL "
                ;
                $where = " ( {$where} ) AND ( {$andWhere} ) ";
            }
        }
        $select->where($where);

        $products = $this->_connection->fetchCol($select);
        Mage::register(AW_Catalogpermissions_Helper_Data::DISABLED_PRICE_PROD_SCOPE, $products, true);
    }

    public function cacheDisabledProducts()
    {
        Mage::register(AW_Catalogpermissions_Helper_Data::DISABLED_PROD_SCOPE, array(), true);

    }

    public function cacheDisabledCategories($_storeId = null, $_customerGroup = null)
    {
        if (is_null($_storeId)) {
            $_storeId = $this->getStoreId();
        }
        if (is_null($_customerGroup)) {
            $_customerGroup = AW_Catalogpermissions_Helper_Data::getCustomerGroup();
        }

        if (AW_Catalogpermissions_Model_Observer::$useCache === true) {
            $_cacheTag = AW_Catalogpermissions_Model_Cache::getCacheTag(
                $_customerGroup, $_storeId, AW_Catalogpermissions_Model_Cache::CATEGORY_CACHE_TAG
            );
            $cacheData = AW_Catalogpermissions_Model_Cache::loadCache($_cacheTag);
            $cacheData = @unserialize($cacheData);
            if (is_array($cacheData)) {
                Mage::register(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE, $cacheData, true);
                return array();
            }
        }

        $this->_initResourceConnection();

        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $catCode = $eavAttribute->getIdByCode(
            'catalog_category', AW_Catalogpermissions_Helper_Data::CP_DISABLE_CATEGORY
        );
        $attT = $this->getTable('catalog/category') . "_text";
        $categoryEntityTable = $this->getTable('catalog/category');

        $select = $this->getSelect()
            ->from(array('e' => $categoryEntityTable), array('e.path'))
            ->joinLeft(
                array('_cp_def' => $attT),
                "_cp_def.entity_id = e.entity_id AND _cp_def.attribute_id = {$catCode} AND _cp_def.store_id = 0",
                array()
            )
            ->joinLeft(
                array('_catalogpermissions_store' => $attT),
                "_catalogpermissions_store.entity_id = e.entity_id AND _catalogpermissions_store.attribute_id = {$catCode} AND _catalogpermissions_store.store_id = {$_storeId}",
                array()
            )
        ;
        $where =
            "IF(_catalogpermissions_store.value_id > 0, _catalogpermissions_store.value, _cp_def.value) REGEXP '(^|,){$_customerGroup}(,|$)'"
        ;

        if (Mage::helper('catalogpermissions')->isMssEnabled()) {
            $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/category') . "_int";
            $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
            $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');

            $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
            $attrCodeMss = $eavAttribute->getIdByCode(
                'catalog_category',
                AW_Catalogpermissions_Helper_Data::MSS_DISABLE_CATEGORY
            );

            $select
                ->joinLeft(
                    array('_cp_mss_rule' => $attMssTable),
                    "_cp_mss_rule.entity_id = e.entity_id "
                    . " AND _cp_mss_rule.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule.store_id = {$this->getStoreId()}",
                    array()
                )
                ->joinLeft(
                    array('_mss_index' => $mssIndexTable),
                    "_mss_index.rule_id = _cp_mss_rule.value"
                    ." AND _mss_index.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule' => $mssRulesTable),
                    "_mss_rule.filter_id = _cp_mss_rule.value"
                    ." AND _mss_rule.is_active = '1'",
                    array()
                )
                ->joinLeft(
                    array('_cp_mss_rule_def' => $attMssTable),
                    "_cp_mss_rule_def.entity_id = e.entity_id "
                    . " AND _cp_mss_rule_def.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_mss_index_def' => $mssIndexTable),
                    "_mss_index_def.rule_id = _cp_mss_rule_def.value"
                    ." AND _mss_index_def.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule_def' => $mssRulesTable),
                    "_mss_rule_def.filter_id = _cp_mss_rule_def.value"
                    ." AND _mss_rule_def.is_active = 1",
                    array()
                )
            ;

            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $andWhere =
                    " IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) IS NULL "
                ;
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) = '{$customerId}'"
                    . " AND IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) = 1"
                ;
                $where = " ( ( {$where} ) AND ( {$andWhere} ) OR ( {$orWhere} ) ) ";
            }
            else {
                $andWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) = '{$customerId}'"
                    . " AND IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) = 1"
                    . " OR IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) IS NULL "
                ;
                $where = " ( {$where} ) AND ( {$andWhere} ) ";
            }
        }
        $select->where($where);

        $categories = $this->_connection->fetchCol($select);

        $select = $this->getSelect()
            ->from(array('e' => $categoryEntityTable), array('e.entity_id'));
        if (empty($categories)) {
            return false;
        }
        $like = null;
        for ($i = 0; $i < count($categories); $i++) {
            $like .= "e.path LIKE '{$categories[$i]}' OR e.path LIKE '{$categories[$i]}/%'";
            if ($i != (count($categories) - 1)) {
                $like .= ' OR ';
            }
        }
        $select->where($like);
        $disabledCategories = $this->_connection->fetchCol($select);
        Mage::register(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE, $disabledCategories, true);

        return $disabledCategories;
    }

    /**
     * Get cached not active categories
     *
     * @param null|int $storeId
     *
     * @return array Not active categories list
     */
    public function getNotActiveCategories($storeId = null)
    {
        $storeId || $storeId = Mage::app()->getStore()->getId();
        if (!isset($this->inactiveCategoriesCache[$storeId])) {
            /** @var Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection $collection */
            $collection = Mage::getModel('catalog/category')->setStoreId($storeId)->getCollection();
            $collection->addAttributeToFilter('is_active', 0);
            $this->inactiveCategoriesCache[$storeId] = $collection->getAllIds();
        }
        return $this->inactiveCategoriesCache[$storeId];
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
     */
    public function addDisCategoryAttrFilter($collection)
    {

        if ($this->_alreadyProcessed($collection)) {
            return $collection;
        }
        $this->_initResourceConnection();
        $cn = $this->_getCorrelationName($collection);

        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $attrCode = $eavAttribute->getIdByCode(
            'catalog_category', AW_Catalogpermissions_Helper_Data::CP_DISABLE_CATEGORY
        );
        $attT = Mage::getSingleton('core/resource')->getTableName('catalog/category') . "_text";

        $collection->getSelect()
            ->joinLeft(
                array('_cp_def' => $attT),
                "_cp_def.entity_id = {$cn}.entity_id AND _cp_def.attribute_id = {$attrCode} AND _cp_def.store_id = 0",
                array()
            )
            ->joinLeft(
                array('_catalogpermissions_store' => $attT),
                "_catalogpermissions_store.entity_id = {$cn}.entity_id AND _catalogpermissions_store.attribute_id = {$attrCode} AND _catalogpermissions_store.store_id = {$this->getStoreId(
                )}",
                array()
            )
        ;

        $where =
            "IF(_catalogpermissions_store.value_id > 0, _catalogpermissions_store.value, _cp_def.value) NOT REGEXP '(^|,)"
                . AW_Catalogpermissions_Helper_Data::getCustomerGroup()
                . "(,|$)' OR (_cp_def.value IS NULL AND _catalogpermissions_store.value IS NULL)";
        $orWhere = null;

        if (Mage::helper('catalogpermissions')->isMssEnabled()) {
            $attMssTable = Mage::getSingleton('core/resource')->getTableName('catalog/category') . "_int";
            $mssIndexTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/index_customer');
            $mssRulesTable = Mage::getSingleton('core/resource')->getTableName('marketsuite/filters');

            $customerId = AW_Catalogpermissions_Helper_Data::getCustomerId();
            $attrCodeMss = $eavAttribute->getIdByCode(
                'catalog_category',
                AW_Catalogpermissions_Helper_Data::MSS_DISABLE_CATEGORY
            );

            $collection
                ->getSelect()
                ->joinLeft(
                    array('_cp_mss_rule' => $attMssTable),
                    "_cp_mss_rule.entity_id = {$cn}.entity_id "
                    . " AND _cp_mss_rule.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule.store_id = {$this->getStoreId()}",
                    array()
                )
                ->joinLeft(
                    array('_mss_index' => $mssIndexTable),
                    "_mss_index.rule_id = _cp_mss_rule.value"
                    ." AND _mss_index.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule' => $mssRulesTable),
                    "_mss_rule.filter_id = _cp_mss_rule.value"
                    ." AND _mss_rule.is_active = '1'",
                    array()
                )
                ->joinLeft(
                    array('_cp_mss_rule_def' => $attMssTable),
                    "_cp_mss_rule_def.entity_id = {$cn}.entity_id "
                    . " AND _cp_mss_rule_def.attribute_id = {$attrCodeMss} "
                    . " AND _cp_mss_rule_def.store_id = 0",
                    array()
                )
                ->joinLeft(
                    array('_mss_index_def' => $mssIndexTable),
                    "_mss_index_def.rule_id = _cp_mss_rule_def.value"
                    ." AND _mss_index_def.customer_id = '{$customerId}'",
                    array()
                )
                ->joinLeft(
                    array('_mss_rule_def' => $mssRulesTable),
                    "_mss_rule_def.filter_id = _cp_mss_rule_def.value"
                    ." AND _mss_rule_def.is_active = 1",
                    array()
                )
            ;

            if (
                AW_Catalogpermissions_Helper_Data::MSS_MODE == AW_Catalogpermissions_Helper_Data::MSS_MODE_XOR
            ) {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_rule.is_active, _mss_rule_def.is_active) = 1";
                $andWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( {$where} OR {$orWhere} ) AND ( {$andWhere} ) ";
            }
            else {
                $orWhere =
                    "IF(_cp_mss_rule.value_id > 0, _mss_index.customer_id, _mss_index_def.customer_id) IS NULL"
                    . " OR (_mss_rule.is_active IS NULL AND _mss_rule_def.is_active IS NULL) "
                    . " OR (_mss_rule.is_active IS NULL AND _cp_mss_rule.value_id IS NOT NULL) "
                ;
                $where = " ( " . $where . " ) OR ( " . $orWhere . " ) ";
            }
        }
        $collection->getSelect()->where($where);

        $collection->getSelect()->where(
            "{$cn}.entity_id NOT IN (?)", Mage::registry(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE)
        );

        return $collection;
    }

    /**
     * Add inactive categories to category tree object
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat|Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree $category_tree
     */
    public function addInactiveCategories($category_tree)
    {
        $disabled_categories = Mage::registry(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE);
        if ($disabled_categories and is_array($disabled_categories)) {
            $category_tree->addInactiveCategoryIds(
                $disabled_categories
            );
        }
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     *
     * @return string
     */
    private function _getCorrelationName($collection)
    {
        list($correlationName) = ($collection->getSelect()->getPart(Zend_Db_Select::COLUMNS));
        return array_shift($correlationName);
    }

    /**
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     *
     * @return bool
     */
    private function _alreadyProcessed($collection)
    {
        $where = $collection->getSelect()->getPart(Zend_Db_Select::WHERE);
        foreach ($where as $part) {
            if (preg_match('#_catalogpermissions_store\.value_id#is', $part)) {
                return true;
            }
        }
        return false;
    }

    public function isCategoryDisabled($categoryId)
    {
        if (($disabledCategories = Mage::registry(AW_Catalogpermissions_Helper_Data::DIABLED_CATEGS_SCOPE)) === null) {
            $disabledCategories = $this->cacheDisabledCategories();
        }
        return is_array($disabledCategories) && in_array($categoryId, $disabledCategories);
    }
}
