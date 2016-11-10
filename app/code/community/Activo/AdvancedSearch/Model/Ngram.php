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
 
class Activo_AdvancedSearch_Model_Ngram extends Mage_Core_Model_Abstract
{
    const XML_PATH_AC_ENABLED       = 'activo_advancedsearch/autocomplete/enabled';
    const XML_PATH_AC_MAX_RESULTS   = 'activo_advancedsearch/autocomplete/maxresults';
    const XML_PATH_AC_MAX_WORDS     = 'activo_advancedsearch/autocomplete/maxwords';
    const XML_PATH_AC_INSTOCK       = 'activo_advancedsearch/autocomplete/instockonly';
    const XML_PATH_AC_ATTRIBUTES    = 'activo_advancedsearch/autocomplete/attributes';
    const XML_PATH_AC_STOPWORDS     = 'activo_advancedsearch/autocomplete/stopwords';
    
    protected function _construct()
    {
        $this->_init('advancedsearch/ngram');
    }
    
    public function refreshNgrams()
    {
        $this->getResource()->refreshNgrams();
    }
}