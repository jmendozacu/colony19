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

class Activo_AdvancedSearch_Model_Resource_Dictionary extends Mage_Core_Model_Mysql4_Abstract
{
    protected $attrCodes = array();
    protected $delimiters = array(',', '.', '|', );
    protected $_numWords;

    protected $_write;
    protected $_read;
    protected $_tableWord;
    protected $_attributes;
    protected $_attributeValues;
    protected $_stopWords;


    protected function _construct()
    {
        $this->_init('advancedsearch/dictionary', 'id');

        $eavConfig = Mage::getSingleton('eav/config');
        $this->attrCodes[] = $eavConfig->getAttribute('catalog_product', 'name')->getId();

        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_read  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_tableWord = Mage::getSingleton('core/resource')->getTableName('activo_advancedsearch_word');
        $this->_attributes = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_ATTRIBUTES);
        $this->getAttributeValues();
    }

    public function build($mainDictionary)
    {
        //$this->emptyIndexes();
        $this->_numWords = 0;

        //get attributes to collect
        $arrayAttrs = explode(',',$this->_attributes);

        //get product Collection
        $pCollection = Mage::getModel('catalog/product')->getCollection();
        $pCollection->addAttributeToFilter('visibility', array('neq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE));
        $pCollection->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
        $pCollection->addAttributeToSelect($arrayAttrs, 'left');

        //walk through the collection
        Mage::getSingleton('core/resource_iterator')->walk(
            $pCollection->getSelect(),
            array(array($this, 'productCallback')),
            array('arg1' => '====')
            );

    }

    function productCallback($args)
    {
        //get attributes to collect
        $arrayAttrs = explode(',',Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_ATTRIBUTES));

        foreach ($arrayAttrs as $attrCode)
        {
            if(array_key_exists($attrCode, $this->_attributeValues))
            {
                $attributeOptions = explode(',', $args['row'][$attrCode]);
                foreach ($attributeOptions as $optionId)
                {
                    $this->parseStrings($this->_attributeValues[$attrCode][$optionId]);
                }
            }
            else
            {
                $this->parseStrings($args['row'][$attrCode]);
            }

        }
    }

    protected function getAttributeValues()
    {
        $this->_attributeValues = array();

        foreach( explode(',',$this->_attributes) as $attrCode)
        {
            $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product',$attrCode);
            $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
            if($attribute->getFrontendInput()=='select' || $attribute->getFrontendInput()=='multiselect')
            {
                $this->_attributeValues[$attrCode] = array();

                foreach ( $attribute->getSource()->getAllOptions(true, true) as $option)
                {
                    $this->_attributeValues[$attrCode][$option['value']] = $option['label'];
                }
            }
        }
    }

    public function parseStrings($string)
    {
        //TODO: adopt a way to handle various language delimitors
        $numWords = 0;

        //Clean words from special characters
        $string = trim(preg_replace('/[^a-zA-Z0-9\'\/]/'," ", $string));

        $words = explode(" ", $string);

        foreach ($words as $word)
        {
            $word = trim($word);

            //Learn only words longer then 3 letters
            if (strlen($word)>=3 && !in_array($word, $this->_getStopWords()))
            {
                $this->learnNewWord($word);
                $numWords++;
            }
        }

        return $numWords;
    }

    public function learnNewWord($word)
    {
        $word = trim($word);
        $word = trim($word, "'");

        $soundex = $this->_write->quote(soundex($word));
        $word    = $this->_write->quote($word);

        try {
            $sql = "INSERT IGNORE INTO {$this->_tableWord} (soundex,word) VALUES ($soundex,$word)";
            $this->_write->query($sql);
        }
        catch (Exception $e)
        {
            Mage::log('Problem inserting word ('.$word.') into DB.');
        }
    }

    protected function _getStopWords()
    {
        if (!$this->_stopWords)
        {
            $this->_stopWords = explode(",", Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_STOP_WORDS));
        }

        return $this->_stopWords;
    }

    public function getCorrectedPhrase($phrase)
    {
        $newWords = $this->correct($phrase);

        for ($i=0; $i<1; $i++)
        {
            $gotCorrection = false;
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
                            $suggestText.= " ".$correction[$i]['word'];
                        }
                        else
                        {
                            if ($i==0)
                            {
                                $suggestText.= " ".$word;
                            }
                            else
                            {
                                $suggestText.= " ".$correction[0]['word'];
                            }
                        }
                    }
                    else
                    {
                        $suggestText.= " ".$word;
                    }
                }
            }
        }

        if ($gotCorrection)
        {
            return trim($suggestText);
        }
        else
        {
            return $phrase;
        }
    }

    public function correct($phrase)
    {
        $words = explode(" ",$phrase);

        $newWords = array();
        foreach ($words as $word)
        {
            $newWords[] = array( $word => $this->correctWord($word) );
        }

        return $newWords;
    }

    protected function correctWord($word)
    {

        if (!Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SUGGEST_ALWAYSON) && $this->isWordCorrect($word))
        {
            return false; //nothing to correct
        }
        else
        {
            $soundex = soundex($word);
            $lookupBySoundex = "SELECT word FROM {$this->_tableWord} WHERE soundex='$soundex'";

            $words = $this->_read->fetchAll($lookupBySoundex);

            if (count($words)==0)
            {
                return false; //no suggestions available
            }
            else
            {
                foreach ($words as $key => &$w)
                {
                    $w['dist'] = levenshtein($word, $w['word']);
                }
                //Sort array of similar words by levenshtein distance
                uasort($words, array($this, 'distSort'));

                //Cut array and only keep top NNN results from config options
                $words = array_slice($words, 0, Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SUGGEST_CORRECTMAX));

                return $words;
            }
        }
    }

    protected function distSort($a, $b)
    {
        if ($a['dist'] == $b['dist']) return 0;

    	return (int)$a['dist'] < (int)$b['dist'] ? -1 : 1;
    }

    protected function isWordCorrect($word)
    {
        $word = $this->_read->quote($word);

        $lookupWord = "SELECT id FROM {$this->_tableWord} WHERE word=$word";
        $wordId = $this->_read->fetchRow($lookupWord);

        return $wordId != false;
    }

    public function emptyIndexes()
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tableWord = Mage::getSingleton('core/resource')->getTableName('activo_advancedsearch_word');

        $write->query("TRUNCATE $tableWord");
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if ( !$object->getId() ) {
            $object->setCreatedAt(now());
        }
        $object->setModifiedAt(now());
        return $this;
    }

    public function getSimilarWords($word, $level)
    {
        $hasSimilarWords = false;

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableWord = Mage::getSingleton('core/resource')->getTableName('activo_advancedsearch_word');

        //get similar words by soundex
        $word = trim($word);
        $soundex = $read->quote(soundex($word));
        $lookupBySoundex = "SELECT word FROM ".$tableWord." WHERE soundex=".$soundex;
        $words = $read->fetchAll($lookupBySoundex);

        if (count($words)>0)
        {
            foreach ($words as $key => &$w)
            {
                $levenshteinDistance = levenshtein($word, $w['word']);

                if ($levenshteinDistance > $level)
                {
                    unset($words[$key]);
                }
                else
                {
                    $w['dist'] = $levenshteinDistance;
                }
            }
            //Sort array of similar words by levenshtein distance
            uasort($words, array($this, 'distSort'));

            $hasSimilarWords = true;
        }

        //add similar words based on characters allowed to omit
        if ($modifiedWords = $this->_getModifiedWords($word))
        {
            $words = array_merge($modifiedWords, $words);

            $hasSimilarWords = true;
        }

        // Get correction for the word spelling is any
        $corrected = $this->correctWord($word);
        if ($corrected) {
            $words[]['word'] = $corrected;
        }
        return ($hasSimilarWords ? $words : false);
    }

    protected function _getModifiedWords($word)
    {
        $modifiedWords = array();

        //get characters to omit
        $chars2Omit = explode(',',Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_CHARS_OMMIT));
        $chars2Omit[] = ',';

        foreach($chars2Omit as $chars)
        {
            if (stripos($word, $chars)!==FALSE)
            {
                $modifiedWords[]['word'] = str_replace($chars, "", $word);
            }
        }

        return (count($modifiedWords)>0 ? $modifiedWords : false);
    }
}
