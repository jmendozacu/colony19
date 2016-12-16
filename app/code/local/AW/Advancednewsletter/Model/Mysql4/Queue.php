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


class AW_Advancednewsletter_Model_Mysql4_Queue extends Mage_Newsletter_Model_Mysql4_Queue
{

    protected function _construct()
    {
        $this->_init('advancednewsletter/queue', 'queue_id');
    }

    public function setStores(Mage_Newsletter_Model_Queue $queue)
    {
        $this->_getWriteAdapter()
            ->delete(
                $this->getTable('queue_store_link'),
                $this->_getWriteAdapter()->quoteInto('queue_id = ?', $queue->getId())
            )
        ;

        if (!is_array($queue->getStores())) {
            $stores = array();
        } else {
            $stores = $queue->getStores();
        }
        // store_id = 0 - subscriber assigned to all stores
        $stores[] = 0;

        foreach ($stores as $storeId) {
            $data = array();
            $data['store_id'] = $storeId;
            $data['queue_id'] = $queue->getId();
            $this->_getWriteAdapter()->insert($this->getTable('queue_store_link'), $data);
        }

        $this->removeSubscribersFromQueue($queue);

        if (count($stores) == 0) {
            return $this;
        }
        $subscribers = Mage::getResourceSingleton('advancednewsletter/subscriber_collection')
            ->addFieldToFilter('store_id', array('in' => $stores))
            ->addFilterSubscribed()
            ->addFilterSegments($queue->getTemplate()->getSegments())
            ->load()
        ;

        $subscriberIds = array();
        foreach ($subscribers as $subscriber) {
            $subscriberIds[] = $subscriber->getId();
        }

        if (count($subscriberIds) > 0) {
            $this->addSubscribersToQueue($queue, $subscriberIds);
        }
        return $this;
    }

    public function getTable($entity)
    {
        $entity = preg_replace('#newsletter/#is', '', $entity);
        return parent::getTable($entity);
    }

}
