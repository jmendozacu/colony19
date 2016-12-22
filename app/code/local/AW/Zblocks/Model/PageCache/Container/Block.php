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


class AW_Zblocks_Model_PageCache_Container_Block extends Enterprise_PageCache_Model_Container_Abstract
{
    protected static $_placeholderAttributes = array(
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CUSTOM_POSITION,
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_BLOCK_POSITION,
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_IS_CMS_PAGE,
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CURRENT_CATEGORY_ID,
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CURRENT_PRODUCT_ID
    );

    protected static $_serializedPlaceholderAttributes = array(
        AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_CATEGORY_PATH
    );

    /**
     * Get container individual cache id
     *
     * @return string
     */
    protected function _getCacheId()
    {
        $cacheId = $this->_getCacheTagPrefix() . md5($this->_placeholder->getAttribute('cache_id'));
        return $cacheId;
    }

    protected function _getCacheTagPrefix()
    {
        return 'AW_ZBLOCKS_BLOCK_';
    }

    /**
     * Render block content from placeholder
     *
     * @return string|false
     */
    protected function _renderBlock()
    {
        $block = $this->_getPlaceHolderBlock();
        $placeholderData = $this->getPlaceholderData();
        $block->setPlaceholderData($placeholderData);
        $html = $block->toHtml();
        return $html;
    }


    public function getPlaceholderData()
    {
        $placeholderData = new Varien_Object();

        foreach(self::$_placeholderAttributes as $attribute) {
            $placeholderData->setData(
                $attribute,
                $this->_placeholder->getAttribute($attribute)
            );
        }

        foreach(self::$_serializedPlaceholderAttributes as $serializedAttribute) {
            $placeholderData->setData(
                $serializedAttribute,
                unserialize($this->_placeholder->getAttribute($serializedAttribute))
            );
        }

        return $placeholderData;
    }
}