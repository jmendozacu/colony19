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


class AW_Zblocks_Model_Mysql4_Zblocks_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('zblocks/zblocks');
    }
    
    /**
     * Filters collection by store ids
     * @param $stores
     * @return AW_Featured_Model_Mysql4_Blocks_Collection
     */
    public function addStoreFilter($stores = null, $breakOnAllStores = false) {
        $_stores = array(Mage::app()->getStore()->getId());
        if (is_string($stores)) {
            $_stores = explode(',', $stores);
        }
        if (is_array($stores)) {
            $_stores = $stores;
        }
        if (is_integer($stores)) {
            $_stores = array((string)$stores);
        }
        if (!in_array('0', $_stores)) {
            array_push($_stores, '0');
        }
        if ($breakOnAllStores && $_stores == array(0)) {
            return $this;
        }
        $_sqlString = '(';
        $i = 0;
        foreach ($_stores as $_store) {
            $_sqlString .= sprintf('find_in_set(%s, store_ids)', $this->getConnection()->quote($_store));
            if (++$i < count($_stores)) {
                $_sqlString .= ' OR ';
            }
        }
        $_sqlString .= ')';
        $this->getSelect()->where($_sqlString);
        return $this;
    }
    
    public function addExcludeStoreFilter($stores) {
        if (is_string($stores)) {
            $stores = explode(',', $stores);
        }
        if (is_integer($stores)) {
            $stores = array($stores);
        }
        foreach ($stores as $store) {
            $this->getSelect()->where(sprintf('NOT find_in_set(%s, store_ids)', $this->getConnection()->quote($store)));
        }
        return $this;
    }

    public function includeCustomerGroup($groupID)
    {
         $this->getSelect()->where(
             sprintf('find_in_set(%s, customer_group)', $this->getConnection()->quote($groupID))
         );
         return $this;
    }
    
    public function getFilterFor($zblock)
    {
         $this->getSelect()->where('`zblock_id` = ?', $zblock);
         return $this;
    }

    /*
     * Covers original bug in Varien_Data_Collection_Db
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::HAVING);

        $countSelect->from('', 'COUNT(*)');
        return $countSelect;
    }

    public function joinBlockCount()
    {
        if (!$this->getFlag('block_count_joined')) {
            $contentTable = $this->getTable('zblocks/content');
            $this
                ->getSelect()
                ->joinLeft(
                    new Zend_Db_Expr(
                        "(SELECT COUNT(zblock_id) as block_count, zblock_id"
                        . " FROM {$contentTable}"
                        . " GROUP BY zblock_id)"
                    ),
                    'main_table.zblock_id = t.zblock_id',
                    array('block_count' => 'IFNULL(t.block_count, 0)')
                );
            $this->setFlag('block_count_joined', true);
        }

        return $this;
    }

    public function addPositionColumn()
    {
        if (!$this->getFlag('position_column_added')) {
            $this
                ->getSelect()
                ->columns(
                    array('position' => "if(block_position='custom', CONCAT(block_position_custom, ' *'), block_position)")
                );
            $this->setFlag('position_column_added', true);
        }

        return $this;
    }
}
