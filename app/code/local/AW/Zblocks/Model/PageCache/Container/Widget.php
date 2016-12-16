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


class AW_Zblocks_Model_PageCache_Container_Widget extends AW_Zblocks_Model_PageCache_Container_Block
{
    protected function _getCacheTagPrefix()
    {
        return 'AW_ZBLOCKS_WIDGET_';
    }

    /**
     * Render block content from placeholder
     *
     * @return string|false
     */
    protected function _renderBlock()
    {
        $block = $this->_getPlaceHolderBlock();

        $block->setBlockId($this->_placeholder->getAttribute(AW_Zblocks_Helper_Data::CACHE_KEY_WIDGET_BLOCK_ID));

        return parent::_renderBlock();
    }
}