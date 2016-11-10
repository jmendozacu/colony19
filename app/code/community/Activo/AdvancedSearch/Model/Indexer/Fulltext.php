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


/**
 * CatalogSearch fulltext indexer model
 *
 * @category    Mage
 * @package     Mage_CatalogSearch
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Activo_AdvancedSearch_Model_Indexer_Fulltext extends Mage_CatalogSearch_Model_Indexer_Fulltext
{
    /**
     * Related Configuration Settings for match
     *
     * @var array
     */
    protected $_relatedConfigSettings = array(
        Mage_CatalogSearch_Model_Fulltext::XML_PATH_CATALOG_SEARCH_TYPE,
        Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_WEIGHTED_ATTRIBUTES
    );
    
    
   /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        if (!Mage::helper('advancedsearch/weightedattr')->isWeightedAttributesEnabled()) {
            return parent::getName();
        }

        return Mage::helper('advancedsearch')->__('&#187; Advanced Search Index');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        if (!Mage::helper('advancedsearch/weightedattr')->isWeightedAttributesEnabled()) {
            return parent::getDescription();
        }
        
        return Mage::helper('advancedsearch')->__('Rebuild weighted attributes product search index');
    }
    
    /**
     * Register data required by process in event object
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        parent::_registerEvent($event);
        
        if ($event->getEntity() == Mage_Core_Model_Config_Data::ENTITY) {
            $event->addNewData('catalogsearch_fulltext_reset_search_results', true);
        }
    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        $data = $event->getNewData();

        if (!empty($data['catalogsearch_fulltext_reset_search_results'])) {
            $this->_getIndexer()->resetSearchResults();
        }
        
        parent::_processEvent($event);
    }
}
