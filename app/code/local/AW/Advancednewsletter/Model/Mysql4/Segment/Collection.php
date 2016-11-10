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
 * @version    2.4.7
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancednewsletter_Model_Mysql4_Segment_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/segment');
    }

    public function addCategoryFilter($categoryId)
    {
        $categoryId = (int)$categoryId;
        $this->getSelect()
            ->where(
                "FIND_IN_SET('$categoryId', display_in_category) OR display_in_category = "
                . AW_Advancednewsletter_Helper_Data::ANY_CATEGORY_VALUE
            )
        ;
        return $this;
    }
    
    public function joinSubscribers()
    {        
        $this->getSelect()
            ->columns(
                array(
                    'subscribers_count' => new Zend_Db_Expr(
                        "(SELECT(COUNT(IF(FIND_IN_SET(main_table.code, segments_codes), main_table.segment_id, NULL)))"
                        . " from {$this->getTable('advancednewsletter/subscriber')})"
                    )
                )
            );
     
        return $this;
    }

    public function addStoreFilter($storeIds)
    {
        if (!is_array($storeIds)) {
            $storeIds = array($storeIds);
        }
        
        $conditions = array();
        foreach ($storeIds as $storeId) {
            $storeId = (int)$storeId;
            $conditions[] = "FIND_IN_SET('$storeId', main_table.display_in_store)";
        }
        $conditions[] = 'display_in_store = 0';
        $this->getSelect()->where('('.implode(' OR ', $conditions).')');
        return $this;
    }

    public function addDefaultCategoryFilter($categoryId)
    {
        $categoryId = (int)$categoryId;
        $this->getSelect()
            ->where(
                "FIND_IN_SET('$categoryId', default_category) OR default_category = "
                . AW_Advancednewsletter_Helper_Data::ANY_CATEGORY_VALUE
            )
        ;
        return $this;
    }

    public function addDefaultStoreFilter($storeId)
    {
        $storeId = (int)$storeId;
        $this->getSelect()
            ->where("FIND_IN_SET('$storeId', default_store) OR default_store = 0")
        ;
        return $this;
    }
    
}