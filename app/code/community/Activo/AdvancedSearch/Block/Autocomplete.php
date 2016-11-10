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
 
class Activo_AdvancedSearch_Block_Autocomplete extends Mage_CatalogSearch_Block_Autocomplete
{
    protected $_suggestData = null;
    protected $_storeId;

    protected function _toHtml()
    {
        $this->_storeId = Mage::app()->getStore()->getId();
        $query = $this->helper('advancedsearch')->getQueryText(false);
        
        $html = "";
        if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Ngram::XML_PATH_AC_ENABLED))
        {
            $html.= $this->_getAutoCompleteHtml($query);
        }
        
        if (Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SUGGEST_ENABLED))
        {
            $html.= $this->_getAutoSuggestHtml($query);
        }

        
        if ($html == "")
        {
            return "";
        }
        else
        {
            $html = "<ul><li style=\"display:none\"></li>" . $html . "</ul>";
            
            return $html;
        }
    }
    
    protected function _getAutoCompleteHtml($query)
    {
        $html = "";
        $phrases = Mage::getResourceSingleton('advancedsearch/ngram')->getAutocomplete($query,$this->_storeId);
        
        if (is_null($phrases) || count($phrases)==0)
        {
            return "";
        }
        else
        {
            foreach ($phrases as $phrase)
            {
                $html.= "<li title=\"{$phrase}\" class=\"suggest odd\">{$phrase}</li>";
            }
            
            return $html;
        }
    }
    
    protected function _getAutoSuggestHtml($query)
    {
        $html = "";
        $newWords = Mage::getResourceSingleton('advancedsearch/dictionary')->correct($query);
            
        for ($i=0; $i<Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SUGGEST_CORRECTMAX); $i++)
        {
            $gotCorrection = false;
            $suggestHtml = "";
            $suggestText = "";

            foreach ($newWords as $token)
            {
                foreach ($token as $word => $correction)
                {                    
                    if (is_array($correction))
                    {
                        if (isset($correction[$i])) $gotCorrection = true;

                        if (isset($correction[$i]['word']))
                        {
                            $suggestHtml.= " <strong>".$correction[$i]['word']."</strong>";
                            $suggestText.= " ".$correction[$i]['word'];
                        }
                        else
                        {
                            if ($i==0)
                            {
                                $suggestHtml.= " ".$word;
                                $suggestText.= " ".$word;
                            }
                            else
                            {
                                $suggestHtml.= " <strong>".$correction[0]['word']."</strong>";
                                $suggestText.= " ".$correction[0]['word'];
                            }
                        }
                    }
                    else
                    {
                        $suggestHtml.= " ".$word;
                        $suggestText.= " ".$word;
                    }
                }
            }

            if ($gotCorrection)
            {
                $html.= '<li title="'.trim($suggestText).'" class="suggest even">'.$this->helper('advancedsearch')->__('Did you mean %s ?',$suggestHtml).'</li>';
            }
        }
        
        return $html;
    }
        
    
    protected function _cleanPhrase($phrase)
    {
        return htmlspecialchars($phrase);
    }

}
