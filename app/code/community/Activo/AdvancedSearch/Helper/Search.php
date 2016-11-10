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

class Activo_AdvancedSearch_Helper_Search extends Mage_CatalogSearch_Helper_Data
{

    public function getQueryText($enableCorrect = true)
    {
        if (!isset($this->_queryText)) {
            $this->_queryText = $this->_getRequest()->getParam($this->getQueryParamName());
            if ($this->_queryText === null) {
                $this->_queryText = '';
            } else {
                /* @var $stringHelper Mage_Core_Helper_String */
                $stringHelper = Mage::helper('core/string');
                $this->_queryText = is_array($this->_queryText) ? ''
                    : $stringHelper->cleanString(trim($this->_queryText));

                $maxQueryLength = $this->getMaxQueryLength();
                if ($maxQueryLength !== '' && $stringHelper->strlen($this->_queryText) > $maxQueryLength) {
                    $this->_queryText = $stringHelper->substr($this->_queryText, 0, $maxQueryLength);
                    $this->_isMaxLength = true;
                }
                
                
            }
        }
        
        if (Mage::getStoreConfig('activo_advancedsearch/serp/usecorrected')==1 &&
            $enableCorrect && 
            $this->_getRequest()->getParam('a') != '1')
        {
            $correctedQuery = Mage::getResourceModel('advancedsearch/dictionary')->getCorrectedPhrase($this->_queryText);
            if ($correctedQuery != $this->_queryText && Mage::getStoreConfig('activo_advancedsearch/serp/searchinstead')==1)
            {
                $url = Mage::getModel('core/url');
                $url->setQueryParam('q', $this->_queryText);
                $url->setQueryParam('a', '1');
                
                $this->addNoteMessage("Search instead for <a href='{$url->getUrl('catalogsearch/result')}'>{$this->_queryText}</a>");
            }
            
            $this->_queryText = $correctedQuery;
        }
        
        return $this->_queryText;
    }
    
}