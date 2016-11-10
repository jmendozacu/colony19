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


class AW_Advancednewsletter_Helper_Subscriber extends Mage_Core_Helper_Abstract
{

    public function updateSegments($order, $segmentsCut, $segmentsPaste)
    {
        $email = $order->getCustomerEmail();
        try {
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
            $newSubscriberSegments = array();
            if ($subscriber->getId()) {
                foreach ($subscriber->getSegmentsCodes() as $subscriberSegment) {
                    if (!in_array($subscriberSegment, $segmentsCut))
                        $newSubscriberSegments[] = $subscriberSegment;
                }

                $newSubscriberSegments = array_unique(array_merge($segmentsPaste, $newSubscriberSegments));
                if (Mage::helper('advancednewsletter')->isArrayValuesEmpty($newSubscriberSegments))
                    $subscriber->unsubscribeFromAll();
                else {
                    $arrayToWrite = array('segments_codes' => $newSubscriberSegments);
                    if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
                        $arrayToWrite['status'] = AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                    }

                    /* change name if subscriber is guest */
                    if (!$subscriber->getCustomerId()) {
                        $arrayToWrite = $this->_addSubscriberName($arrayToWrite, $order);
                    }

                    $subscriber->forceWrite($arrayToWrite);
                }
            } else {
                /* new subscriber */
                $customerId = $order->getCustomerId();

                $params = array('store_id' => $order->getStoreId());

                if ($customerId) {
                    $subscriber->setCustomer(Mage::getModel('customer/customer')->load($customerId));
                } else {
                    $params = $this->_addSubscriberName($params, $order);
                }
                $subscriber->subscribe($email, $segmentsPaste, $params);
            }
        } catch (Exception $ex) {
            Mage::helper('awcore/logger')->log($this, 'Segments update failed - ' . $ex->getMessage());
        }
    }

    protected function _addSubscriberName($params, $order)
    {
        $params['first_name'] = $order->getCustomerFirstname();
        $params['last_name'] = $order->getCustomerLastname();
        if (null === $params['first_name'] || null === $params['last_name']) {
            $billingAddress = $order->getBillingAddress();
            if (null === $billingAddress) {
                $billingAddress = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress();
            }
            $params['first_name'] = $billingAddress->getFirstname();
            $params['last_name'] = $billingAddress->getLastname();
        }
        return $params;
    }
}