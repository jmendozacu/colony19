<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

class Activo_AdvancedSearch_Model_Resource_Fulltext extends Mage_CatalogSearch_Model_Resource_Fulltext
{
    protected $_stopWords;

    protected $_weightedAttributesArray;
    protected $_attributesArray;
    protected $_attributeValues;

    protected $_table;


    /**
     * Init resource model
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_table = Mage::getSingleton('core/resource')->getTableName('advancedsearch/weighted_search');
    }

    /**
     * Prepare results for query
     *
     * @param Mage_CatalogSearch_Model_Fulltext $object
     * @param string $queryText
     * @param Mage_CatalogSearch_Model_Query $query
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    public function prepareResult($object, $queryText, $query)
    {
        $storeId = (int)$query->getStoreId();
        $adapter = $this->_getWriteAdapter();

//        $preparedTerms = Mage::getResourceHelper('catalogsearch')
//            ->prepareTerms($queryText, $query->getMaxQueryWords());

        $bind = array();
        $like = array();
        $likeCond  = '';


        $helper = Mage::getResourceHelper('core');
        $allWords = $words = Mage::helper('core/string')->splitWords($queryText, true, $query->getMaxQueryWords());
        $stopWords = $this->_getStopWords($storeId);
        foreach ($words as $word)
        {
            if (!in_array($word, $stopWords))
            {
                if (!in_array($word, $allWords)) $allWords[] = $word;
                if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_SIMILARITY_ENABLE))
                {
                    $similarityLevel = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_SIMILARITY_LEVEL);
                    $similarWords = Mage::helper('advancedsearch')->getSimilarWords($word, $similarityLevel);
                    $similars = array();
                    $similars[] = $helper->getCILike('s.data_index', $word, array('position' => 'any'));
                    if ($similarWords)
                    {
                        foreach ($similarWords as $sWord)
                        {
                            if (in_array($sWord['word'], $stopWords)) continue;

                            // Also build an array of all words we will be using in the union below. This would include all similar words as well
                            if (!in_array($sWord['word'], $allWords)) $allWords[] = $sWord['word'];
                            $similars[] = $helper->getCILike('s.data_index', $sWord['word'], array('position' => 'any'));
                        }
                    }
                    $like[] = '('. join(' OR ', $similars) .')';
                }
                else
                {
                    $like[] = $helper->getCILike('s.data_index', $word, array('position' => 'any'));
                }
            }
        }
        if ($like)
        {
            if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_REFINEMENT_ENABLE, $storeId)) {
                $likeCond = '(' . join(' AND ', $like) . ')';
            } else {
                $likeCond = '(' . join(' OR ', $like) . ')';
            }
        }

        $select = $adapter->select();

        if (Mage::helper('advancedsearch/weightedattr')->isWeightedAttributesEnabled()) {
            // Weighted Attributes Search
            $innerTableAlias = 'i';
            $mainTableAlias = 's';

            $fields = array(
                'query_id' => new Zend_Db_Expr($query->getId()),
                'product_id' => 'entity_id',
                'weight',
                'data_index'
            );

            $unionParts = array();
            foreach ($allWords as $word) {
                $part = $adapter->select()
                    ->from(array($innerTableAlias => $this->_table), $fields)
                    ->where($innerTableAlias.'.store_id = ?', (int)$query->getStoreId())
                    ;

                if (!in_array($word, $this->_getStopWords($storeId))) {
                    $part->where($helper->getCILike($innerTableAlias.'.data_index', $word, array('position' => 'any')));
                } else {
                    //don't return a thing but keep the structure
                    $part->where('1=0');
                }

                $unionParts[] = $part;
            }

            //Build wrapper query level 1
            $fields1 = array(
                'query_id',
                'product_id',
                'weight'        => 'SUM(weight)',
                'data_index'   => 'GROUP_CONCAT(data_index)'
            );
            $wrap1 = $adapter->select()
                ->from(array('w1' => new Zend_Db_Expr('('. $adapter->select()->union($unionParts, Zend_Db_Select::SQL_UNION_ALL) .')')), $fields1)
                ->group(array('product_id'))
                ;

            //Build wrapper query level 2 (with refinement logic)
            $fields2 = array(
                'query_id',
                'product_id',
                'weight'    => 'SUM(weight)'
            );
            $select->from(array($mainTableAlias => new Zend_Db_Expr('(' . $wrap1 . ')')), $fields2)
                ->group(array('product_id'))
                ->order(array('weight DESC'));

            if ($likeCond != '') {
                $select->where($likeCond);
            }

        } else {
            //Fulltext regular search
            $mainTableAlias = 's';
            $fields = array(
                'query_id' => new Zend_Db_Expr($query->getId()),
                'product_id',
            );
            $select->from(array($mainTableAlias => $this->getMainTable()), $fields)
                ->joinInner(array('e' => $this->getTable('catalog/product')),
                    'e.entity_id = s.product_id',
                    array())
                ->where($mainTableAlias.'.store_id = ?', (int)$query->getStoreId());

            $select->columns(array('relevance'  => new Zend_Db_Expr(0)));

            if ($likeCond != '') {
                $select->where($likeCond);
            }
        }

        $sql = $adapter->insertFromSelect($select,
            $this->getTable('catalogsearch/result'),
            array(),
            Varien_Db_Adapter_Interface::INSERT_ON_DUPLICATE);
        $adapter->query($sql, $bind);

        $query->setIsProcessed(1);

        return $this;
    }

    protected function _getStopWords($storeId)
    {
        if (!$this->_stopWords)
        {
            $this->_stopWords = array();
        }

        if (!isset($this->_stopWords[$storeId]))
        {
            $this->_stopWords[$storeId] = explode(",", Mage::getStoreConfig('activo_advancedsearch/serp/stopwords', $storeId));
        }

        return $this->_stopWords[$storeId];
    }

    /**
     * Regenerate search index for store(s)
     *
     * @param  int|null $storeId
     * @param  int|array|null $productIds
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    public function rebuildIndex($storeId = null, $productIds = null)
    {
        if (!Mage::helper('advancedsearch/weightedattr')->isWeightedAttributesEnabled()) {
            return parent::rebuildIndex($storeId, $productIds);
        }

        if (is_null($storeId)) {
            $storeIds = array_keys(Mage::app()->getStores());
            foreach ($storeIds as $storeId) {
                $this->_rebuildStoreIndexAdvanced($storeId, $productIds);
            }
        } else {
            $this->_rebuildStoreIndexAdvanced($storeId, $productIds);
        }

        return $this;
    }

    /**
     * Regenerate search index for specific store
     *
     * @param int $storeId Store View Id
     * @param int|array $productIds Product Entity Id
     * @return Mage_CatalogSearch_Model_Resource_Fulltext
     */
    protected function _rebuildStoreIndexAdvanced($storeId, $productIds = null)
    {
        $this->cleanIndex($storeId, $productIds);

        //get attributes to collect
        $arrayAttrs = array_diff( $this->getAttributesArray($storeId), array('sku') );

        //prepare attribute option values
        $this->prepareAttributeValues($storeId);

        //get product Collection
        $pCollection = Mage::getModel('catalog/product')->getCollection();
        $pCollection->setStoreId($storeId);
        if ($productIds) {
            $pCollection->addIdFilter($productIds);
        }
        $pCollection->addAttributeToSelect($arrayAttrs, 'left');
        $pCollection->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE))
            ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));

//
//        if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_INSTOCK))
//        {
//            $pCollection->joinField(
//                'stock_status',
//                'cataloginventory/stock_status',
//                'stock_status',
//                'product_id=entity_id',
//                '{{table}}.stock_id=1',
//                'left'
//            )->addAttributeToFilter('stock_status', array('eq' => Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK));
//        }
        //Mage::log((string)$pCollection->getSelect());

        //walk through the collection, much better performance
        Mage::getSingleton('core/resource_iterator')->walk(
            $pCollection->getSelect(),
            array(array($this, 'productCallback')),
            array('storeId' => $storeId)
            );
    }

    function productCallback($args)
    {
        $storeId = $args['storeId'];

        //get attributes to collect
        $weightedAttributes = $this->getWeightedAttributes($storeId);

        foreach ($weightedAttributes as $attrCode => $weight)
        {
            $value = '';

            if(array_key_exists($attrCode, $this->_attributeValues[$storeId]))
            {
                $attributeOptions = explode(',', $args['row'][$attrCode]);
                foreach ($attributeOptions as $optionId)
                {
                    $value = $this->cleanString($this->_attributeValues[$storeId][$attrCode][$optionId]);
                }
            }
            else
            {
                $value = $this->cleanString($args['row'][$attrCode]);
            }

            if (!empty($value)) {
                $this->addRow($args['row']['entity_id'], $storeId, $weight, $value);
            }
        }
    }

    function getWeightedAttributes($storeId = null)
    {
        if (!$this->_weightedAttributesArray) {
            $this->_weightedAttributesArray = array();
        }

        if (!isset($this->_weightedAttributesArray[$storeId])) {
            $this->_weightedAttributesArray[$storeId] = Mage::helper('advancedsearch/weightedattr')->getConfigValue($storeId);
        }

        return $this->_weightedAttributesArray[$storeId];
    }

    function getAttributesArray($storeId = null)
    {
        if (!$this->_attributesArray) {
            $this->_attributesArray = array();
        }

        if (!isset($this->_attributesArray[$storeId])) {
            $this->_attributesArray[$storeId] = array();
            foreach ($this->getWeightedAttributes($storeId) as $key => $value) {
                $this->_attributesArray[$storeId][] = $key;
            }
        }

        return $this->_attributesArray[$storeId];
    }

    protected function prepareAttributeValues($storeId = null)
    {
        if (!$this->_attributeValues) {
            $this->_attributeValues = array();
        }

        if (!isset($this->_attributeValues[$storeId])) {
            $this->_attributeValues[$storeId] = array();

            foreach( $this->getAttributesArray($storeId) as $attrCode )
            {
                $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product',$attrCode);
                $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                if($attribute->getFrontendInput()=='select' || $attribute->getFrontendInput()=='multiselect')
                {
                    $this->_attributeValues[$storeId][$attrCode] = array();

                    foreach ( $attribute->setStoreId($storeId)->getSource()->getAllOptions(true, true) as $option)
                    {
                        $this->_attributeValues[$storeId][$attrCode][$option['value']] = $option['label'];
                    }
                }
            }
        }
    }

    public function cleanString($string)
    {
        //Lowercase only
        //$string = strtolower($string);
        //Remove html tags
        $string = strip_tags($string);
        //Get rid of these characters
        $string = str_replace("\n", " ", $string);
        $string = str_replace("\r", " ", $string);
        //Escape special characters so we can insert into DB
        //$string = mysql_real_escape_string($string);
        $string = html_entity_decode($string, ENT_COMPAT | ENT_HTML401, "UTF-8");

        return $string;
    }


    public function addRow($entityId, $storeId, $weight, $value)
    {
        $valueArray = array(
            'entity_id'     => $entityId,
            'store_id'      => $storeId,
            'weight'        => $weight,
            'data_index'         => $value
        );

        try {
            $this->_getWriteAdapter()->insert($this->_table, $valueArray);
        }
        catch (Exception $e)
        {
            Mage::log('Weighted Attr Search Index: Problem adding row. Value array:'.  print_r($valueArray,true));
            Mage::log('Weighted Attr Search Index Exception: '.$e->getMessage());
        }
    }

    /**
     * Remove entity data from fulltext search table
     *
     * @param int $storeId
     * @param int $entityId
     * @return Mage_CatalogSearch_Model_Resource_Fulltext_Engine
     */
    public function cleanIndex($storeId = null, $entityId = null)
    {
        if (!Mage::helper('advancedsearch/weightedattr')->isWeightedAttributesEnabled()) {
            parent::cleanIndex($storeId, $entityId);
        }

        $where = array();

        if (!is_null($storeId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('store_id=?', $storeId);
        }
        if (!is_null($entityId)) {
            $where[] = $this->_getWriteAdapter()->quoteInto('entity_id IN (?)', $entityId);
        }

        $this->_getWriteAdapter()->delete($this->_table, $where);

        return $this;
    }
}
