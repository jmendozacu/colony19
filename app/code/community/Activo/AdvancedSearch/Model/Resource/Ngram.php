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
 
class Activo_AdvancedSearch_Model_Resource_Ngram extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_maxWords;
    protected $_minChars;
    protected $_stopWords;
    protected $_maxAutoComplete;
    protected $_table;
    protected $_write;
    protected $_attributeValues;
    protected $_attributesArray;


    protected function _construct()
    {
        $this->_init('advancedsearch/ngram', 'id');
        
        $this->_maxWords = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_MAX_WORDS);
        $this->_minChars = 1;
        $this->_maxAutoComplete = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_MAX_RESULTS);
        
        $this->_table = Mage::getSingleton('core/resource')->getTableName('activo_advancedsearch_ngram');
    }
    
    public function refreshNgrams($storeId = null)
    {
        if (is_null($storeId)) {
            $storeIds = array_keys(Mage::app()->getStores());
            foreach ($storeIds as $storeId) {
                $this->_refreshStoreNgrams($storeId);
            }
        } else {
            $this->_refreshStoreNgrams($storeId);
        }

        return $this;
    }
            
    protected function _refreshStoreNgrams($storeId)
    {
        //$this->emptyIndex();
        
        //get attributes to collect
        $arrayAttrs = array_diff( $this->getAttributesArray($storeId), array('sku') );
        
        //get product Collection
        $pCollection = Mage::getModel('catalog/product')->getCollection();
        $pCollection->setStoreId($storeId);
        $pCollection->addAttributeToSelect($arrayAttrs, 'left');
        $pCollection->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));
        $pCollection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
        if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_INSTOCK))
        {
            $pCollection->joinField(
                'stock_status',
                'cataloginventory/stock_status',
                'stock_status',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )->addAttributeToFilter('stock_status', array('eq' => Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK));
        }
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
        
        foreach ($this->getAttributesArray($storeId) as $attrCode)
        {
            $attributeStoreValues = $this->getAttributeValues($storeId);
            
            if(array_key_exists($attrCode, $attributeStoreValues))
            {
                $attributeOptions = explode(',', $args['row'][$attrCode]);
                foreach ($attributeOptions as $optionId)
                {
                    $this->parseStrings($this->cleanString($attributeStoreValues[$attrCode][$optionId]), $storeId);
                }
            }
            else
            {
                $this->parseStrings($this->cleanString($args['row'][$attrCode]), $storeId);
            }
            
        }
    }
    
    public function parseStrings($string, $storeId)
    {
        //TODO: do not split numbers with dots used as decimal separator
        $exploded = $this->multiexplode(array(",",".","|",":","(",")","/"," - ","_","+","=","!","?"),$string);
        
        foreach ($exploded as $phrase)
        {
            $this->addAllPhrases(explode(" ",$phrase), $storeId);
        }        
    }
    
    public function multiexplode ($delimiters,$string)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);
        return  $launch;
    }
    
    /**
     * Collect and add all phrases and sub phrases within phrase array
     * 
     * @param type $phrases array of tokens
     */
    public function addAllPhrases($phrases, $storeId)
    {
        $cleanPhrases = array();
        foreach ($phrases as $phrase)
        {
            if(!in_array($phrase, $this->_getStopWords($storeId)))
            {
                $cleanPhrases[] = $phrase;
            }
        }
        
        for ($i=0; $i<count($cleanPhrases); $i++)
        {
            $phrase = "";
            for ($j=0; $j<$this->_maxWords; $j++)
            {
                if ($i+$j < count($cleanPhrases))
                {
                    $phrase .= $cleanPhrases[$i+$j] . " ";
                    $this->addPhrase(trim($phrase), $storeId);
                }
            }
        }
    }
    
    public function addPhrase($phrase, $storeId)
    {
        if (strlen($phrase) > $this->_minChars)
        {
            try {
                
                $this->_getWrite()->insertOnDuplicate(
                    $this->_table,
                    array(
                        'store_id'  => $storeId,
                        'phrase'    => $phrase,
                        'frequency' => 1
                    ),
                    array('frequency' => new Zend_Db_Expr('frequency+1'))
                );
            } 
            catch (Exception $e) 
            {
                Mage::log('Advanced Search Index: Problem adding phrase into ngram table: '.$phrase.' storeId: '.$storeId);
                Mage::log('Advanced Search Index Exception: '.$e->getMessage());
            }
        }
    }
    
    protected function _getStopWords($storeId)
    {
        if (!$this->_stopWords)
        {
            $this->_stopWords = array();
        }
        
        if (!isset($this->_stopWords[$storeId]))
        {
            $this->_stopWords[$storeId] = explode(",", Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_STOPWORDS, $storeId));
        }
        
        return $this->_stopWords[$storeId];
    }
    
    public function cleanString($string)
    {
        //Lowercase only
        $string = strtolower($string);
        //Remove html tags
        $string = strip_tags($string);
        //Get rid of these characters
        $string = str_replace("\n", " ", $string);
        $string = str_replace("\r", " ", $string);
        //Escape special characters so we can insert into DB
        //$string = mysql_real_escape_string($string);
        
        return $string;
    }
    
    public function emptyIndex()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $table = Mage::getSingleton('core/resource')->getTableName('activo_advancedsearch_ngram');
        
        $write->query("TRUNCATE $table");
    }
    
    protected function getAttributeValues($storeId)
    {
        if (!$this->_attributeValues) {
            $this->_attributeValues = array();
        }
            
        if (!isset($this->_attributeValues[$storeId])) {
            $this->_attributeValues[$storeId] = array();

            foreach( $this->getAttributesArray($storeId) as $attrCode )
            {
                $attribute = Mage::getModel('catalog/resource_eav_attribute')
                                ->loadByCode(Mage_Catalog_Model_Product::ENTITY, $attrCode);

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
        
        return $this->_attributeValues[$storeId];
    }
    
    protected function getAttributesArray($storeId = null)
    {
        if (!$this->_attributesArray) {
            $this->_attributesArray = array();
        }

        if (!isset($this->_attributesArray[$storeId])) {
            $this->_attributesArray[$storeId] = array();
            
            $this->_attributesArray[$storeId] = explode(',',Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_ATTRIBUTES, $storeId));
        }

        return $this->_attributesArray[$storeId];
    }

    protected function _getWrite() {
        if (!$this->_write) {
            $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        }

        return $this->_write;
    }
    
    public function getAutocomplete($string, $storeId)
    {
        $max = is_numeric($this->_maxAutoComplete) ? $this->_maxAutoComplete : 10;
        
        if (!is_null($string))
        {
            $read  = Mage::getSingleton('core/resource')->getConnection('core_read');
            $string = $read->quote($this->cleanString($string).'%');

            try {
                $sql = "SELECT phrase FROM {$this->_table} WHERE store_id={$storeId} AND phrase LIKE {$string} ORDER BY frequency DESC LIMIT $max";
                $matches = $read->fetchCol($sql);

                return $matches;
            } 
            catch (Exception $e) 
            {
                Mage::log("Advanced Search getAutocomplete($string) Exception: ".$e->getMessage());
            }
        }
        
        return null;
    }
}