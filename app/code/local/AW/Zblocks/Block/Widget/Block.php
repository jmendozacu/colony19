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

 
class AW_Zblocks_Block_Widget_Block extends AW_Zblocks_Block_Block implements Mage_Widget_Block_Interface
{   
    protected function _toHtml()
    {
        Mage::helper('zblocks')->setWidgetIdentity($this->getBlockId());
        return parent::_toHtml();
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $items = parent::getCacheKeyInfo();
        $items[AW_Zblocks_Helper_Data::CACHE_KEY_WIDGET_BLOCK_ID] = $this->getBlockId();
        return $items;
    }

    public function getRenderData($isForCacheKey = false)
    {
        $renderData = parent::getRenderData($isForCacheKey);
        $renderData->setData(
            AW_Zblocks_Helper_Data::CACHE_KEY_BLOCK_BLOCK_POSITION,
            AW_Zblocks_Model_Source_Position::WIDGET_POSITION
        );
        return $renderData;
    }
}
