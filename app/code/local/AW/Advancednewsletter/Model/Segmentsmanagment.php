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
 * @package    AW_Advancednewsletter
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


/**
 * DEPRICATED. Was used in AW_Advancednewsletter < 2.0. Now used for sync with 2.0
 * version and compatibility with other extensions
 */
class AW_Advancednewsletter_Model_Segmentsmanagment extends Mage_Core_Model_Abstract
{
    /**
     * @deprecated
     * @param int$storeId
     *
     * @return AW_Advancednewsletter_Model_Mysql4_Segment_Collection
     */
    public function getStoreDefaultSegments($storeId)
    {
        return Mage::getModel('advancednewsletter/segment')
            ->getCollection()
            ->addDefaultStoreFilter($storeId)
            ->addBindParam('frontend_visibility', 1)
            ->addOrder('display_order', Varien_Data_Collection::SORT_ORDER_ASC)
        ;
    }

    /**
     * @deprecated
     * @param bool $withoutAll
     */
    public function getSegmentList($withoutAll = false)
    {
        return Mage::getModel('advancednewsletter/segment')->getSegmentOptionArray();
    }

}