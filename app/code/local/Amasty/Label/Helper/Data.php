<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Label
 */
class Amasty_Label_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_labels = null;

    public function getLabels($product, $mode = 'category', $useJs = false)
    {
        $html = '';
        if (! $this->_validateProduct($product)) {
            return $html;
        }
        $applied = false;
        $labelCollection = $this->_getCollection();
        if (0 < $labelCollection->getSize()) {
            foreach ($labelCollection as $label) {
                if ($label->getIsSingle() && $applied) {
                    continue;
                }
                $label->init($product, $mode);
                if ($label->isApplicable()) {
                    $applied = true;
                    $html .= $this->_generateHtml($label);
                } elseif ($label->getUseForParent() && ($product->isConfigurable() || $product->isGrouped())) {
                    $usedProds = $this->getUsedProducts($product);
                    foreach ($usedProds as $child) {
                        $label->init($child, $mode, $product);
                        if ($label->isApplicable()) {
                            $applied = true;
                            $html .= $this->_generateHtml($label);
                        }
                    }
                }
            }
        }

        return $html;
    }

    protected function _validateProduct($product) {
        $id = $product->getId();
        $ids = Mage::registry('amlabel_scripts_ids');

        if ($id && is_array($ids) && in_array($id, $ids)) {
            return false;
        }
        $ids[] = $id;
        Mage::unregister('amlabel_scripts_ids');
        Mage::register('amlabel_scripts_ids', $ids);
        return true;
    }

    protected function _getCollection()
    {
        if (is_null($this->_labels)) {
            $id            = Mage::app()->getStore()->getId();
            $this->_labels = Mage::getModel('amlabel/label')->getCollection()
                                 ->addFieldToFilter('stores', array('like' => "%,$id,%"))
                                 ->addFieldToFilter('is_active', 1)
                                 ->setOrder('pos', 'asc')
                                 ->load();
        }

        return $this->_labels;
    }

    protected function _generateHtml($label)
    {
        $block = Mage::app()->getLayout()->createBlock(
            'amlabel/label',
            'amlabel_label_block',
            array(
                'label' => $label
            )
        );

        $html = $block->setLabel($label)->toHtml();
        return $html;
    }

    public function getUsedProducts($product)
    {
        if ($product->isConfigurable()) {
            return $product->getTypeInstance(true)->getUsedProducts(null, $product);
        } else { // product is grouped
            return $product->getTypeInstance(true)->getAssociatedProducts($product);
        }
    }
}
