<?php
/**
 * MageWorx
 * SeoAll Extension
 *
 * @category   MageWorx
 * @package    MageWorx_SeoAll
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */
class MageWorx_SeoAll_Model_UrlRenderer_TrailingSlash_Review extends MageWorx_SeoAll_Model_UrlRenderer_TrailingSlash_Abstract
{
    /**
     *
     * {@inheritDoc}
     */
    protected function _getDefaultTrailingSlashMethod()
    {
        if ($this->_helperAdapter->isReviewFriendlyUrlEnable()) {
            return self::TRAILING_SLASH_ADD;
        }
        return parent::_getDefaultTrailingSlashMethod();
    }

}