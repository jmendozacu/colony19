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

class AW_Catalogpermissions_Model_Mysql4_Search_Collection extends Enterprise_Search_Model_Resource_Collection
{
    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            list($query, $params) = $this->_prepareBaseParams();

            $helper = Mage::helper('enterprise_search');
            $searchSuggestionsEnabled = ($this->_searchQueryParams != $this->_generalDefaultQuery
                && $helper->getSolrConfigData('server_suggestion_enabled'));
            if ($searchSuggestionsEnabled) {
                $params['solr_params']['spellcheck'] = 'true';
                $searchSuggestionsCount = (int) $helper->getSolrConfigData('server_suggestion_count');
                $params['solr_params']['spellcheck.count']  = $searchSuggestionsCount;
                $params['spellcheck_result_counts']         = (bool) $helper->getSolrConfigData(
                    'server_suggestion_count_results_enabled');
            }
            $result = $this->_engine->getIdsByQuery($query, $params);
            $ids = (array) $result['ids'];

            if (count($ids) > 0) {
                $enabledIds = Mage::helper('catalogpermissions/connection')->removeDisabledProducts($ids);
                $params['filters']['id'] = $enabledIds;

                $result = $this->_engine->getIdsByQuery($query, $params);
                $ids = (array) $result['ids'];
            }

            if ($searchSuggestionsEnabled && !empty($result['suggestions_data'])) {
                $this->_suggestionsData = $result['suggestions_data'];
            }

            $this->_totalRecords = count($ids);
        }
        return intval($this->_totalRecords);
    }

    protected function _beforeLoad()
    {
        $ids = array();
        if ($this->_engine) {
            list($query, $params) = $this->_prepareBaseParams();

            if ($this->_sortBy) {
                $params['sort_by'] = $this->_sortBy;
            }

            $needToLoadFacetedData = false;
            $result = $this->_engine->getIdsByQuery($query, $params);
            $ids    = (array) $result['ids'];

            if (count($ids) > 0) {
                if ($this->_pageSize !== false) {
                    $page              = ($this->_curPage  > 0) ? (int) $this->_curPage  : 1;
                    $rowCount          = ($this->_pageSize > 0) ? (int) $this->_pageSize : 1;
                    $params['offset']  = $rowCount * ($page - 1);
                    $params['limit']   = $rowCount;
                }

                $needToLoadFacetedData = (!$this->_facetedDataIsLoaded && !empty($this->_facetedConditions));
                if ($needToLoadFacetedData) {
                    $params['solr_params']['facet'] = 'on';
                    $params['facet'] = $this->_facetedConditions;
                }

                $enabledIds = Mage::helper('catalogpermissions/connection')->removeDisabledProducts($ids);
                $params['filters']['id'] = $enabledIds;

                $result = $this->_engine->getIdsByQuery($query, $params);
                $ids    = (array) $result['ids'];
            }

            if ($needToLoadFacetedData) {
                $this->_facetedData = $result['faceted_data'];
                $this->_facetedDataIsLoaded = true;
            }
        }

        $this->_searchedEntityIds = &$ids;
        $this->getSelect()->where('e.entity_id IN (?)', $this->_searchedEntityIds);

        $this->_storedPageSize = $this->_pageSize;
        $this->_pageSize = false;

        Mage::dispatchEvent('catalog_product_collection_load_before', array('collection' => $this));

        return $this;
    }
}