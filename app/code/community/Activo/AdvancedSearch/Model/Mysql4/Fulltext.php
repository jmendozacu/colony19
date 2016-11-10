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

class Activo_AdvancedSearch_Model_Mysql4_Fulltext extends Mage_CatalogSearch_Model_Mysql4_Fulltext
{
    protected $_stopWords;
    
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
        if (!Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_REFINEMENT_ENABLE))
        {
            return parent::prepareResult($object, $queryText, $query);
        }

        $adapter = $this->_getWriteAdapter();

        $bind = array(
                ':query' => $queryText
            );
        $like = array();
        $likeCond  = '';


        //$helper = Mage::getResourceHelper('core');
        $words = Mage::helper('core/string')->splitWords($queryText, true, $query->getMaxQueryWords());
        foreach ($words as $word)
        {
            if (!in_array($word, $this->_getStopWords()))
            {
                if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_SIMILARITY_ENABLE))
                {
                    $similarityLevel = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_SIMILARITY_LEVEL);
                    $similarWords = Mage::helper('advancedsearch')->getSimilarWords($word, $similarityLevel);
                    $similars = array();
                    //$similars[] = $helper->getCILike('s.data_index', $word, array('position' => 'any'));
                    $similars[] = "`s`.`data_index` LIKE '%{$word}%'";
                    if ($similarWords)
                    {
                        foreach ($similarWords as $sWord)
                        {
                            //$similars[] = $helper->getCILike('s.data_index', $sWord['word'], array('position' => 'any'));
                            $similars[] = "`s`.`data_index` LIKE '%{$sWord['word']}%'";
                        }
                    }
                    $like[] = '('. join(' OR ', $similars) .')';
                }
                else
                {
                    //$like[] = $helper->getCILike('s.data_index', $word, array('position' => 'any'));
                    $like[] = "`s`.`data_index` LIKE '%{$word}%'";
                }
            }
        }
        if ($like) 
        {
            $likeCond = '(' . join(' AND ', $like) . ')';
        }

        $mainTableAlias = 's';
        $fields = array(
            'query_id' => new Zend_Db_Expr($query->getId()),
            'product_id',
        );
        $select = $adapter->select()
            ->from(array($mainTableAlias => $this->getMainTable()), $fields)
            ->joinInner(array('e' => $this->getTable('catalog/product')),
                'e.entity_id = s.product_id',
                array())
            ->where($mainTableAlias.'.store_id = ?', (int)$query->getStoreId());

        $select->columns(array('relevance'  => new Zend_Db_Expr(0)));
        $where = $likeCond;

        if ($where != '') {
            $select->where($where);
        }
            
        $sql = sprintf("INSERT INTO `{$this->getTable('catalogsearch/result')}` "
            . "(SELECT STRAIGHT_JOIN '%d', `s`.`product_id`, MATCH (`s`.`data_index`) "
            . "AGAINST (:query IN BOOLEAN MODE) FROM `{$this->getMainTable()}` AS `s` "
            . "INNER JOIN `{$this->getTable('catalog/product')}` AS `e` "
            . "ON `e`.`entity_id`=`s`.`product_id` WHERE (%s) AND `s`.`store_id`='%d')"
            . " ON DUPLICATE KEY UPDATE `relevance`=VALUES(`relevance`)",
            $query->getId(),
            $likeCond,
            $query->getStoreId()
        );

        //Mage::log((string)$select);

        $adapter->query($sql, $bind);

        $query->setIsProcessed(1);
            
        return $this;
    }

    protected function _getStopWords()
    {
        if (!$this->_stopWords)
        {
            $this->_stopWords = explode(",", Mage::getStoreConfig('activo_advancedsearch/serp/stopwords'));
        }
        
        return $this->_stopWords;
    }
}
