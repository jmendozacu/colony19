<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */
class Amasty_Label_Block_Label extends Mage_Core_Block_Abstract
{
    protected $_sizes  = array();

    public function __construct($data)
    {
        parent::__construct();
        if (array_key_exists('label', $data)) {
            $this->setLabel($data['label']);

            $id      = $this->getLabel()->getProduct()->getId();
            $labelId = $this->getLabel()->getId();

            $this->addData(array(
                'cache_lifetime' => 86400,
                'cache_tags' => array(
                    Mage_Catalog_Model_Product::CACHE_TAG . '_' . $id,
                    Amasty_Label_Model_Label::CACHE_TAG   . '_' . $labelId,
                ),
            ));
        }
    }

    public function getCacheKeyInfo()
    {
        return array(
            'AMASTY_LABEL_BLOCK',
            Mage::app()->getStore()->getStoreId(),
            $this->getLabel()->getId(),
            $this->getLabel()->getMode(),
            $this->getLabel()->getProduct()->getId()
        );
    }

    protected function _toHtml()
    {
        $label = $this->getLabel();
        $imgUrl = $label->getImageUrl();

        if (empty($this->_sizes[$imgUrl])) {
            $this->_sizes[$imgUrl] = $label->getImageInfo();
        }

        $positionClass = $label->getCssClass();
        $customStyle = $label->getStyle();

        if ($label->getMode() == 'cat') {
            $textStyle = $label->getCatTextStyle();
            $imgWidth  = $label->getCatImageWidth();
        } else {
            $textStyle = $label->getProdTextStyle();
            $imgWidth  = $label->getProdImageWidth();
        }
        $imgWidth  = ($imgWidth)? $imgWidth . '%': '';
        $imgWidth  = str_replace('%%', '%', $imgWidth);
        if(!$imgWidth && array_key_exists('w', $this->_sizes[$imgUrl])) {
            $imgWidth = $this->_sizes[$imgUrl]['w'];
        }
        $imgWidth  = ($imgWidth)? $imgWidth : 'auto';

        if(array_key_exists('h', $this->_sizes[$imgUrl]) && $this->_sizes[$imgUrl]['h']) {
            $customStyle .= ' max-height: '. $this->_sizes[$imgUrl]['h'] . ';';
        }

        $customStyle .= ' max-width: 100%;';
        if ($textStyle) {
            $textStyle = 'style="' . $textStyle . '"';
        }

        $backgroundImg = '; ';
        if ($imgUrl) {
            $backgroundImg = '; background: url(' . $imgUrl . ') no-repeat 0 0; ';
        }
        $textBlockStyle = 'style="width:' . $imgWidth . $backgroundImg . $customStyle . '"';
        $html  = '<div class="amlabel-' . $label->getId() . ' amlabel-table2 top-left" ' . $label->getJs() . ' >';
        $html .= '  <div class="amlabel-txt2 ' . $positionClass . '" ' . $textBlockStyle . ' ><div class="amlabel-txt" ' . $textStyle . '>' . $label->getText() . '</div></div>';
        $html .= '</div>';

        return $html;
    }
}
