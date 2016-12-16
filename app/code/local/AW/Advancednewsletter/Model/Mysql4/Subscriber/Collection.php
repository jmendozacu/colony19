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


class AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Queue joined flag
     *
     * @var boolean
     */
    protected $_queueJoinedFlag = false;

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/subscriber');
        $this->_map['fields']['customer_type'] = 'IF(main_table.customer_id is null,0,1)';
    }

    public function addCustomerType()
    {
        if (preg_match('/^1.3/', Mage::getVersion())) {
            $this->getSelect()
                ->from(null, array('customer_type' => new Zend_Db_Expr('IF(main_table.customer_id is null, 0, 1)')))
            ;
        } else {
            $this->getSelect()
                ->columns(array('customer_type' => new Zend_Db_Expr($this->_getMappedField('customer_type'))))
            ;
        }
        return $this;
    }

    public function addFilterSubscribed()
    {
        $this->getSelect()
            ->where('main_table.status = ?', AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED)
        ;
        return $this;
    }

    public function addFilterUnsubscribed()
    {
        $this->getSelect()
            ->where('main_table.status = ?', AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
        ;
        return $this;
    }

    public function addFilterSubscribedUnsubscribed()
    {
        $this->getSelect()
            ->where('main_table.status = ?', AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
            ->orWhere('main_table.status = ?', AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED)
        ;
        return $this;
    }

    public function addFilterSegments($segments)
    {
        $conditions = array();
        foreach ($segments as $segment) {
            $op = $segment->getIsMss() ? 'and' : 'or';
            $conditions[$op][] = "find_in_set('".$segment->getCode()."', main_table.segments_codes)";
        }
        if (!empty($conditions)) {
            $operands = array('or', 'and');
            foreach ($operands as $op) {
                if (isset($conditions[$op])) {
                    $this->getSelect()
                        ->where(implode(" {$op} ", $conditions[$op]))
                    ;
                }
            }
        }
        return $this;
    }

    public function removeSegment($code)
    {
        $adapter = $this->getSelect()->getAdapter();
        $subscriber = Mage::getSingleton('core/resource')->getTableName('advancednewsletter/subscriber');
        try {

            /* only removed segment */
            $sql = "UPDATE {$subscriber} SET `segments_codes` = '' WHERE `segments_codes`='{$code}'";
            $adapter->exec($sql);
            
            /*  removed segment is in between others */
            $sql = "UPDATE {$subscriber} SET `segments_codes` = REPLACE(`segments_codes`, ',{$code},',',')";
            $adapter->exec($sql);

            /* removed  is first */
            $sql = "UPDATE {$subscriber} SET "
            . "`segments_codes` = SUBSTRING( `segments_codes` , ( POSITION( ',' IN `segments_codes` ) +1 ) )"
            . "WHERE `segments_codes` REGEXP '^{$code},'";
            $adapter->exec($sql);

            /* removed  is last */
            $sql = "UPDATE {$subscriber} SET "
            . "`segments_codes` =  "
                . "SUBSTRING( `segments_codes` , 1, (  LENGTH( `segments_codes` ) - length( '{$code}' ) - 1 ) )"
            . "WHERE `segments_codes` REGEXP ',{$code}$'";
            $adapter->exec($sql);
        } catch (Exception $exc) {
            
        }
        return $this;
    }


    /**
     * Set using of links to only unsendet letter subscribers.
     */
    public function useOnlyUnsent( )
    {
        if ($this->_queueJoinedFlag) {
            $this->getSelect()->where("link.letter_sent_at IS NULL");
        }
        return $this;
    }

    /**
     * Adds customer info to select
     *
     * @return AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection
     */
    public function showCustomerInfo()
    {
        $customer = Mage::getModel('customer/customer');
        /* @var $customer Mage_Customer_Model_Customer */
        $firstname  = $customer->getAttribute('firstname');
        $lastname   = $customer->getAttribute('lastname');

        $this->getSelect()
            ->joinLeft(
                array('customer_lastname_table'=>$lastname->getBackend()->getTable()),
                'customer_lastname_table.entity_id=main_table.customer_id
                AND customer_lastname_table.attribute_id = '.(int) $lastname->getAttributeId(),
                array('customer_lastname'=>'value')
            )
            ->joinLeft(
                array('customer_firstname_table'=>$firstname->getBackend()->getTable()),
                'customer_firstname_table.entity_id=main_table.customer_id
                AND customer_firstname_table.attribute_id = '.(int) $firstname->getAttributeId() . '
                ',
                array('customer_firstname'=>'value')
            )
        ;
        return $this;
    }

    /**
     * Set loading mode subscribers by queue
     *
     * @param Mage_Newsletter_Model_Queue $queue
     *
     * @return $this
     */
    public function useQueue(Mage_Newsletter_Model_Queue $queue)
    {
        $queueLinkTable = Mage::getSingleton('core/resource')->getTableName('advancednewsletter/queue_link');

        $this->getSelect()
            ->join(array('link'=>$queueLinkTable), "link.subscriber_id = main_table.id", array())
            ->where("link.queue_id = ? ", $queue->getId())
        ;
        $this->_queueJoinedFlag = true;
        return $this;
    }

    /**
     * Sets flag for customer info loading on load
     *
     * @return AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection
     */
    public function showStoreInfo()
    {
        $this->getSelect()->join(
            array('store' => Mage::getSingleton('core/resource')->getTableName('core/store')),
            'store.store_id = main_table.store_id',
            array('group_id', 'website_id')
        );
        return $this;
    }

    /**
     * Filter collection by specified store ids
     *
     * @param array|int $storeIds
     * @return AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection
     */
    public function addStoreFilter($storeIds)
    {
        if (is_array($storeIds)) {
            $this->getSelect()->where('main_table.store_id IN (?)', $storeIds);
        } else {
            $this->getSelect()->where('main_table.store_id = ?', $storeIds);
        }
        return $this;
    }

    public function joinCustomerTable()
    {
        $this
            ->getSelect()
            ->joinLeft(
                array('ce' => $this->getTable('customer/entity')),
                'ce.email = main_table.email',
                array('customer_group_id' => 'IF(ce.group_id IS NULL, 0, ce.group_id)')
            )
            ->group('main_table.email');

        return $this;
    }

    public function getSelectCountSql()
    {
        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(Zend_Db_Select::GROUP);

        return $countSelect;
    }
}