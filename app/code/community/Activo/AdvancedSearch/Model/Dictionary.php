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
 
class Activo_AdvancedSearch_Model_Dictionary extends Mage_Core_Model_Abstract
{
    const XML_PATH_ATTRIBUTES               = 'activo_advancedsearch/searchsuggest/attributes';
    const XML_PATH_STOP_WORDS               = 'activo_advancedsearch/searchsuggest/stopwords';
    const XML_PATH_SUGGEST_ENABLED          = 'activo_advancedsearch/searchsuggest/enabled';
    const XML_PATH_SUGGEST_ALWAYSON         = 'activo_advancedsearch/searchsuggest/alwayson';
    const XML_PATH_SUGGEST_CORRECTMAX       = 'activo_advancedsearch/searchsuggest/correctmax';
    const XML_PATH_SERP_CHARS_OMMIT         = 'activo_advancedsearch/serp/charommit';
    const XML_PATH_SERP_SIMILARITY_ENABLE   = 'activo_advancedsearch/serp/enablesimilarity';
    const XML_PATH_SERP_SIMILARITY_LEVEL    = 'activo_advancedsearch/serp/similaritylevel';
    const XML_PATH_SERP_REFINEMENT_ENABLE   = 'activo_advancedsearch/serp/enablerefinement';
    const XML_PATH_SERP_WEIGHTED_ATTRIBUTES = 'activo_advancedsearch/serp/weighted_attributes';
    
    protected function _construct()
    {
        $this->_init('advancedsearch/dictionary');
    }
    
    public function build()
    {
        $this->getResource()->build($this);
    }
    
    public function correct($word)
    {
        return $this->getResource()->correct($word);
    }
    
    public function getSimilarWords($word, $level=1)
    {
        return $this->getResource()->getSimilarWords($word, $level);
    }
}
