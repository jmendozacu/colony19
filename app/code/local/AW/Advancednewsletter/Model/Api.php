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


class AW_Advancednewsletter_Model_Api
{

    /**
     * Get subscriber by email
     * @param string $email
     * @return AW_Advancednewsletter_Model_Subscriber 
     */
    public function getSubscriber($email)
    {
        return Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
    }

    /**
     * Subscribing process. If you want to skip customer check (if you try to use this function then customer
     * is logged in, email, first and last names will be get from customer info, not from function params) set
     * skip_customer_check param to true ($param['skip_customer_check'] = true)
     * @param string $email
     * @param mixed $segments
     * @param array $params
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function subscribe($email, $segments, $params = array())
    {
        Mage::getModel('advancednewsletter/subscriber')->subscribe($email, $segments, $params);
    }

    /**
     * Unsubscribe subscriber with email from segments
     * @param string $email
     * @param array $segments 
     */
    public function unsubscribe($email, $segments)
    {
        $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
        if ($subscriber->getId()) {
            $subscriber->unsubscribe($segments);
        }
    }

    /**
     * Unsubscribe subscriber with email from all segments
     * @param string $email 
     */
    public function unsubscribeFromAll($email)
    {
        $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
        if ($subscriber->getId()) {
            $subscriber->unsubscribeFromAll();
        }
    }

    /**
     * Getting subscribers collection
     * @return AW_Advancednewsletter_Model_Mysql4_Subscriber_Collection
     */
    public function getSubscriberCollection()
    {
        return Mage::getModel('advancednewsletter/subscriber')->getCollection();
    }

    /**
     * Getting segments collection
     * @return AW_Advancednewsletter_Model_Mysql4_Segment_Collection
     */
    public function getSegmentsCollection()
    {
        return Mage::getModel('advancednewsletter/segment')->getCollection();
    }

    /**
     * Return subscriber by customer
     * @param Mage_Customer_Model_Customer $customer
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function getSubscriberByCustomer($customer)
    {
        return Mage::getModel('advancednewsletter/subscriber')->loadByCustomer($customer);
    }

}