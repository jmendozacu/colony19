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


class AW_Advancednewsletter_Model_Sync_Factory implements AW_Advancednewsletter_Model_Sync_Interface
{

    public function subscribe($observer)
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($subscriber->getStoreId())
                ->subscribe($subscriber)
            ;
        } catch (Exception $ex) {
            
        }
    }

    public function unsubscribe($observer)
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($subscriber->getStoreId())
                ->unsubscribe($subscriber)
            ;
        } catch (Exception $ex) {
            
        }
    }

    public function delete($observer)
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($subscriber->getStoreId())
                ->delete($subscriber)
            ;
        } catch (Exception $ex) {
            
        }
    }

    public function forceWrite($observer)
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($subscriber->getStoreId())
                ->forceWrite($subscriber)
            ;
        } catch (Exception $ex) {
            
        }
    }

    public function removeSegment($observer)
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        if ($observer->getSegmentCode()) {
            try {
                foreach (Mage::app()->getStores() as $store) {
                    AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($store->getId())
                        ->removeSegment($observer->getSegmentCode())
                    ;
                }
            } catch (Exception $ex) {
                
            }
        }
    }

}