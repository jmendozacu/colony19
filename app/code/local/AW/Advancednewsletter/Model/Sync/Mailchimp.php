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


class AW_Advancednewsletter_Model_Sync_Mailchimp implements AW_Advancednewsletter_Model_Sync_Interface
{
    /**
     * @var AW_Advancednewsletter_Model_Sync_Mailchimp_ApiInterface
     */
    private $api;

    /**
     * @var int
     */
    private $storeId;

    /**
     * Instantiate of an object of AW_Advancednewsletter_Model_Sync_Interface
     *
     * @param int $storeId
     * @return AW_Advancednewsletter_Model_Sync_Interface
     * @throws AW_Advancednewsletter_Exception
     */
    public static function getInstance($storeId)
    {
        try {
            $instance = new self(array('store_id' => $storeId));
        } catch (Exception $e) {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Couldn\'t connect to MailChimp')
            );
        }
        return $instance;
    }

    /**
     * Test connection to MailChimp
     *
     * @param string $apiKey
     * @throws AW_Advancednewsletter_Exception
     */
    public static function testConnection($apiKey)
    {
        try {
            $instance = new self(array('apikey' => $apiKey));
        } catch (Exception $e) {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Cannot connect to MailChimp')
            );
        }
    }

    /**
     * @param array $params
     * @throws AW_Advancednewsletter_Exception
     */
    private function __construct($params)
    {
        if (isset($params['apikey'])) {
            $this->api = new AW_Advancednewsletter_Model_Sync_Mailchimp_Api30($params['apikey'], '');
        } else {
            $storeId = $params['store_id'];
            $apiKey = Mage::helper('advancednewsletter')->getChimpApiKey($storeId);
            $listId = Mage::helper('advancednewsletter')->getChimpListId($storeId);
            $this->storeId = $storeId;

            if (!$apiKey) {
                throw new AW_Advancednewsletter_Exception(
                    Mage::helper('advancednewsletter')->__('API key is not valid')
                );
            }
            $this->api = new AW_Advancednewsletter_Model_Sync_Mailchimp_Api30($apiKey, $listId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe($subscriber)
    {
        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE) {
            return $this;
        }

        $this->synchronizeSegments();

        if ($this->isStoreOrEmailChanged($subscriber)) {
            $instance = AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($subscriber->getOrigData('store_id'));
            $instance->delete($subscriber, true);
        }

        $this->api->subscribeToList(
            $subscriber->getEmail(),
            $subscriber->getFirstName() ? $subscriber->getFirstName() : '',
            $subscriber->getLastName() ? $subscriber->getLastName() : '',
            $subscriber->getSegmentsCodes()
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribe($subscriber)
    {
        if ($subscriber->getSegmentsCodes()) {
            $this->subscribe($subscriber);
        } else {
            $this->api->unsubscribeFromList(
                $subscriber->getEmail(),
                $subscriber->getFirstName() ? $subscriber->getFirstName() : '',
                $subscriber->getLastName() ? $subscriber->getLastName() : '',
                $subscriber->getSegmentsCodes()
            );
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($subscriber, $useOriginalData = false)
    {
        if ($useOriginalData) {
            $this->api->deleteFromList($subscriber->getOrigData('email'));
        } else {
            $this->api->deleteFromList($subscriber->getEmail());
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function forceWrite($subscriber)
    {
        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            if ($this->isStoreOrEmailChanged($subscriber)) {
                $instance = AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance(
                    $subscriber->getOrigData('store_id')
                );
                $instance->delete($subscriber, true);
                $this->subscribe($subscriber);
            }
            $this->api->unsubscribeFromList(
                $subscriber->getEmail(),
                $subscriber->getFirstName() ? $subscriber->getFirstName() : '',
                $subscriber->getLastName() ? $subscriber->getLastName() : '',
                $subscriber->getSegmentsCodes()
            );
        }

        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $this->subscribe($subscriber);
        }
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLists()
    {
        return $this->api->getAllLists();
    }

    /**
     * {@inheritDoc}
     */
    public function removeSegment($segmentCode)
    {
        $this->api->removeSegmentFromList($segmentCode);
    }

    /**
     * {@inheritDoc}
     */
    public function batchSubscribe($subscribers, $updateNames = true)
    {
        $this->synchronizeSegments();

        $subscribersData = array();
        foreach ($subscribers as $subscriber) {
            $subscriberData = array();
            $subscriberData['email'] = $subscriber->getEmail();
            $subscriberData['segments'] = $subscriber->getSegmentsCodes();
            if ($updateNames) {
                $subscriberData['first_name'] = $subscriber->getFirstName();
                $subscriberData['last_name'] = $subscriber->getLastName();
            }
            $subscribersData[] = $subscriberData;
        }
        $this->api->batchSubscribe($subscribersData, $updateNames);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function batchUnsubscribe($subscribers, $updateNames = true)
    {
        $this->synchronizeSegments();

        $subscribersData = array();
        foreach ($subscribers as $subscriber) {
            $subscriberData = array();
            $subscriberData['email'] = $subscriber->getEmail();
            $subscriberData['segments'] = $subscriber->getSegmentsCodes();
            if ($updateNames) {
                $subscriberData['first_name'] = $subscriber->getFirstName();
                $subscriberData['last_name'] = $subscriber->getLastName();
            }
            $subscribersData[] = $subscriberData;
        }
        $this->api->batchUnsubscribe($subscribersData, $updateNames);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSegments()
    {
        $listSegments = $this->api->getSegmentsFromList();
        $segments = array();
        foreach ($listSegments as $listSegment) {
            $segments[] = $listSegment;
        }
        return $segments;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribers($status, $page, $pageSize)
    {
        $subscribers = $this->api->getSubscribersFromList($status, $page, $pageSize);
        return $subscribers;
    }

    /**
     * Check if subscriber's store or email were changed
     *
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return bool
     */
    private function isStoreOrEmailChanged($subscriber)
    {
        if (
            (
                $subscriber->getOrigData('store_id') &&
                $subscriber->getOrigData('store_id') != $subscriber->getData('store_id')
            ) ||
            (
                $subscriber->getOrigData('email') &&
                $subscriber->getOrigData('email') != $subscriber->getData('email')
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Synchronize current segments
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    private function synchronizeSegments()
    {
        $currentSegments = Mage::getModel('advancednewsletter/segment')->getCollection();
        $listSegments = $this->api->getSegmentsFromList();

        foreach ($currentSegments as $segment) {
            if (!in_array($segment->getCode(), $listSegments)) {
                $this->api->addSegmentToList($segment->getCode());
            }
        }
        return $this;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }
}
