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


class AW_Advancednewsletter_Model_Mysql4_Queue_Collection extends Mage_Newsletter_Model_Mysql4_Queue_Collection
{

    protected function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/queue');
    }

    protected function _addSubscriberInfoToSelect()
    {
        $this->_addSubscribersFlag = false;
        return parent::_addSubscriberInfoToSelect();
    }

    /**
     * Filter collection by specified store ids
     *
     * @param array|int $storeIds
     * @return AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection
     */
    public function addStoreFilter($storeIds)
    {
        $this->getSelect()
            ->joinInner(
                array(
                    'store_link' => $this->getTable('queue_store_link')
                ),
                'main_table.queue_id = store_link.queue_id', array()
            )
            ->where('store_link.store_id IN (?)', $storeIds)
            ->group('main_table.queue_id')
        ;
        return $this;
    }

}
