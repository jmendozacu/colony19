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
 * @package    AW_Zblocks
 * @version    2.5.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Block extends Mage_Core_Block_Template
{
    const MODULE_NAME = 'AW_Zblocks';
    const CATEGORY_CONTROLLER_NAME = 'category';
    const CATEGORY_REQUEST_PARAM = 'cat';

    protected function _toHtml()
    {
        if(AW_Zblocks_Helper_Data::isModuleOutputDisabled()) {
            return '';
        }

        $renderData = $this->getPlaceholderData();
        if (is_null($renderData)) {
            $renderData = $this->getRenderData();
        }

        $html = implode('',
            Mage::helper('zblocks')->getBlocks($renderData)
        );

        return $html;
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $items = parent::getCacheKeyInfo();

        $items = array_merge($items, $this->getIdData());

        $renderData = $this->getRenderData(true);
        $items = array_merge($items, $renderData->getData());

        return $items;
    }

    public function getIdData()
    {
        $idData = array();
        $idData[AW_Zblocks_Helper_Data::CACHE_KEY_STORE_ID] = Mage::app()->getStore()->getId();
        $idData[AW_Zblocks_Helper_Data::CACHE_KEY_CUSTOMER_GROUP_ID] = Mage::helper('zblocks')->getCustomerGroup();
        if ($object = Mage::helper('zblocks')->getCustomerSessionObject()) {
            $idData[AW_Zblocks_Helper_Data::CACHE_KEY_CUSTOMER_SESSION_OBJECT_ID] = $object->getId();
        }
        $idData[AW_Zblocks_Helper_Data::CACHE_KEY_HTTP_REFERER] = Mage::helper('zblocks')->getHttpReferer();
        return $idData;
    }

    public function getRenderData($isForCacheKey = false)
    {
        $renderData = new Varien_Object();
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CUSTOM_POSITION, $this->getPosition());
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_BLOCK_POSITION, $this->getBlockPosition());
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CATEGORY_PATH,
            ($isForCacheKey ? serialize($this->getCategoryPath()) : $this->getCategoryPath())
        );
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CURRENT_CATEGORY_ID, $this->getCurrentCategoryId());
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CURRENT_PRODUCT_ID, $this->getCurrentProductId());
        $renderData->setData(AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_IS_CMS_PAGE, Mage::helper('zblocks')->getIsCmsPage());
        return $renderData;
    }

    private function getCategoryPath()
    {
        $categoryPath = array();
        if (
            $this->getRequest()->getControllerName() == self::CATEGORY_CONTROLLER_NAME
            && $categoryRequestParam = $this->getRequest()->getParam(self::CATEGORY_REQUEST_PARAM)
        ) {
            $categoryPath[] = $categoryRequestParam;
        }
        if ($category = Mage::registry('current_category')) {
            $categoryPath = array_merge($categoryPath, explode('/', $category->getPath()));
        }
        $categoryPath = array_unique($categoryPath);
        foreach($categoryPath as $key => $value) {
            if(!$value) {
                unset($categoryPath[$key]);
            }
        }
        return $categoryPath;
    }

    private function getCurrentCategoryId()
    {
        $currentCategoryId = 0;
        if (
            $this->getRequest()->getControllerName() == self::CATEGORY_CONTROLLER_NAME
            && $categoryRequestParam = $this->getRequest()->getParam(self::CATEGORY_REQUEST_PARAM)
        ) {
            $currentCategoryId = $categoryRequestParam;
        }
        if ($currentProduct = Mage::registry('current_product')) {
            $currentCategoryId = $currentProduct->getCategoryId();
        }
        if ((!$currentCategoryId) && ($category = Mage::registry('current_category'))) {
            $currentCategoryId = $category->getEntityId();
        }
        return $currentCategoryId;
    }


    private function getCurrentProductId()
    {
        $currentProductId = 0;
        if ($currentProduct = Mage::registry('current_product')) {
            $currentProductId = $currentProduct->getId();
        }
        return $currentProductId;
    }

}
