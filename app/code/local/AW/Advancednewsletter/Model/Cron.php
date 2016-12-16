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


class AW_Advancednewsletter_Model_Cron
{
    /**
     *  Sync To Mailchimp.
     *   Count of subcribers which will be synchronized with mailchimp by one cron execution
     *  (one API request)
     */
    const SYNC_TO_MAILCHIMP_PAGE_SIZE = 200;

    /**
     * Sync From Mailchimp. Count of subscribers which will be synchronized with mailchimp by one cron execution
     */
    const SYNC_FROM_MAILCHIMP_PAGE_SIZE = 200;

    protected $_syncFromType;

    /*
     *   Name for export settings
     */
    const SYNC_TO_PARAMS_NAME = 'aw_advancednewsletter_mailchimp_to_params';
    /*
     *   Name for import settings
     */
    const SYNC_FROM_PARAMS_NAME = 'aw_advancednewsletter_mailchimp_from_params';

    /**
     * Sending newsletters by cron
     */
    public function scheduledSend()
    {
        $countOfQueue = 3;
        $countOfSubscritions = 20;

        $collection = Mage::getModel('advancednewsletter/queue')->getCollection()
            ->setPageSize($countOfQueue)
            ->setCurPage(1)
            ->addOnlyForSendingFilter()
            ->load()
        ;
        $collection->walk('sendPerSubscriber', array($countOfSubscritions));
    }

    /**
     *  Import data from MailChimp
     */
    public function syncFromMailchimp()
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }

        $cache = Mage::getModel('advancednewsletter/cache');
        $syncFromParams = $cache->loadCache(self::SYNC_FROM_PARAMS_NAME);
        if (!$syncFromParams)
            return;
        $syncFromParams = unserialize($syncFromParams);

        if (!count($syncFromParams)) {
            return;
        }

        Mage::register('an_disable_autosync', true);

        $mailChimpInstances = $this->getMailChimpInstancesForAllStores($syncFromParams);
        $this->syncAllSegmentsFromMailchimp($mailChimpInstances);

        $instancesIds = array();
        /* Subscrubers sync */
        foreach ($mailChimpInstances as $instance) {

            $storeId = $instance->getStoreId();
            $syncFor = $syncFromParams[$storeId]['sync_for'];

            $pageSize = self::SYNC_FROM_MAILCHIMP_PAGE_SIZE;

            $instancesIds[] = $storeId;
            $page = $syncFromParams[$storeId]['subscr_page'];
            if (!($page === 'none')) {
                $subscribers = $instance->getSubscribers('subscribed', $page, $pageSize);
                if (count($subscribers)) {
                    $this->saveSubscribers(
                        $subscribers,
                        $storeId,
                        $syncFor,
                        AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED
                    );
                    ++$syncFromParams[$storeId]['subscr_page'];
                } else {
                    $syncFromParams[$storeId]['subscr_page'] = 'none';
                }
            }

            $page = $syncFromParams[$storeId]['unsubscr_page'];
            if (!($page === 'none')) {
                $unsubscribers = $instance->getSubscribers('unsubscribed', $page, $pageSize);
                if (count($unsubscribers)) {
                    $this->saveSubscribers(
                        $unsubscribers,
                        $storeId,
                        $syncFor,
                        AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
                    );
                    ++$syncFromParams[$storeId]['unsubscr_page'];
                } else {
                    $syncFromParams[$storeId]['unsubscr_page'] = 'none';
                }
            }
        }

        /*
         *  clear sync params
         *    'none' page to sync subs & unsubs - dont need sync store- remove from params
         *    no stores for sync -  remove sync params
         */
        foreach ($syncFromParams as $key => $value) {
            if ($syncFromParams[$key]['subscr_page'] === 'none' && $syncFromParams[$key]['unsubscr_page'] === 'none') {
                unset($syncFromParams[$key]);
            }

            if (!in_array($key, $instancesIds)) {
                unset($syncFromParams[$key]);
            }
        }

        if (count($syncFromParams)) {
            $cache->saveCache(serialize($syncFromParams), self::SYNC_FROM_PARAMS_NAME);
        } else {
            $cache->removeCache(self::SYNC_FROM_PARAMS_NAME);
        }
        Mage::unregister('an_disable_autosync');
    }

    /**
     * Get MailChimp instances for all stores. Duplicated instances are skipped
     *
     * @param srray $syncFromParams
     * @return array
     */
    private function getMailChimpInstancesForAllStores($syncFromParams)
    {
        $mailChimpInstances = array();
        $usedParams = array();
        foreach ($syncFromParams as $storeId => $storePages) {
            try {
                $apiKey = Mage::helper('advancednewsletter')->getChimpApiKey($storeId);
                $listId = Mage::helper('advancednewsletter')->getChimpListId($storeId);

                if ($this->isApiKeyAndListIdAlreadyUsed($usedParams, $apiKey, $listId)) {
                    continue;
                }
                $instance = AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($storeId);
                $mailChimpInstances[] = $instance;
                $usedParams[] = array(
                    'api_key'   => $apiKey,
                    'list_id'   => $listId
                );
            } catch (Exception $ex) {
                continue;
            }
        }
        return $mailChimpInstances;
    }

    /**
     * Check MailChimp api key and list id to avoid duplicate synchronization
     *
     * @param array $usedParams
     * @param string $apiKey
     * @param string $listId
     * @return bool
     */
    private function isApiKeyAndListIdAlreadyUsed($usedParams, $apiKey, $listId)
    {
        foreach ($usedParams as $param) {
            if ($param['api_key'] == $apiKey && $param['list_id'] == $listId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Synchronize all segments from MailChimp
     *
     * @param $instances
     * @return void
     */
    private function syncAllSegmentsFromMailchimp($instances)
    {
        /** @var AW_Advancednewsletter_Model_Sync_Interface $instance */
        foreach ($instances as $instance) {
            try {
                $segments = $instance->getSegments();
                Mage::getModel('advancednewsletter/segment')->massCreation($segments);
            } catch (Exception $ex) {
                continue;
            }
        }
    }

    /**
     * Save subscribers
     *
     * @param array $subscribersData (array(array('email'=>..,'status'=>.., ...)))
     * @param int $storeId
     * @param string $syncFor
     * @param string $status
     * @return void
     */
    private function saveSubscribers($subscribersData, $storeId, $syncFor, $status)
    {
        foreach ($subscribersData as $subscriberData) {
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($subscriberData['email']);
            switch ($syncFor) {
                case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_STATUSES:
                    if ($subscriber->getId()) {
                        $subscriber->setStatus($status);
                    }
                    break;
                case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_LIST:
                    $customerByEmail = Mage::getModel('customer/customer')
                        ->setStore(Mage::app()->getStore($storeId))
                        ->loadByEmail($subscriberData['email'])
                    ;
                    if ($customerByEmail->getId())
                        $subscriber->setCustomerId($customerByEmail->getId());

                    $subscriber
                        ->setStatus($status)
                        ->setEmail($subscriberData['email'])
                        ->setFirstName($subscriberData['first_name'])
                        ->setLastName($subscriberData['last_name'])
                        ->setSegmentsCodes($subscriberData['segments'])
                        ->setStoreId($storeId);
                    break;
                default:
                    break;
            }

            try {
                $subscriber->save();
            } catch (Exception $ex) {

            }
        }
    }

    /**
     *  Sync to MailChimp
     */
    public function syncToMailchimp()
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }

        $cache = Mage::getModel('advancednewsletter/cache');
        $syncToParams = $cache->loadCache(self::SYNC_TO_PARAMS_NAME);
        if (!$syncToParams) {
            return;
        }

        Mage::register('an_disable_autosync', true);

        $syncToParams = unserialize($syncToParams);
        foreach ($syncToParams as $storeId => $pagesToSync) {
            if (
                Mage::helper('advancednewsletter')->isChimpEnabled($storeId)
                && Mage::helper('advancednewsletter')->getChimpApiKey($storeId)
                && Mage::helper('advancednewsletter')->getChimpListId($storeId)
            ) {

                // sync  subspage
                $page = $syncToParams[$storeId]['subscr_page'];
                $syncToParams[$storeId]['subscr_page'] = $this->exportSubscribersPage(
                    $storeId, 'subscribed', $page, self::SYNC_TO_MAILCHIMP_PAGE_SIZE, $pagesToSync['include_names']
                );

                // sync  unsubspage
                $page = $syncToParams[$storeId]['unsubscr_page'];
                $syncToParams[$storeId]['unsubscr_page'] = $this->exportSubscribersPage(
                    $storeId, 'unsubscribed', $page, self::SYNC_TO_MAILCHIMP_PAGE_SIZE, $pagesToSync['include_names']
                );

                /* remove finished stores */
                if ($pagesToSync['subscr_page'] === 'none' && $pagesToSync['unsubscr_page'] === 'none') {
                    unset($syncToParams[$storeId]);
                }
            } else {
                /* remove disabled store from sync params */
                unset($syncToParams[$storeId]);
            }
        }

        /*  clear & save params  */
        if (count($syncToParams)) {
            $cache->saveCache(serialize($syncToParams), self::SYNC_TO_PARAMS_NAME);
        } else {
            $cache->removeCache(self::SYNC_TO_PARAMS_NAME);
        }

        Mage::unregister('an_disable_autosync');
    }

    /*
     *   Exports subscribers to mailchimp, returns next page to sync
     *
     *  @param int $toreId
     *  @param string $type 'subscribed', 'unsubscribed'
     *  @param int $page
     *  @param int $pageSize
     *  @param bool $includeNames
     *
     *  @result mixed   (int|string)
     */

    private function exportSubscribersPage($storeId, $type, $page, $pageSize, $includeNames)
    {
        if ($page === 'none') {
            return 'none';
        }

        $subscribers = $this->getSubscribers($storeId, $type, $page, $pageSize);

        if (!count($subscribers)) {
            return 'none';
        }

        switch ($type) {
            case 'subscribed':
                try {
                    AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($storeId)
                        ->batchSubscribe($subscribers, $includeNames);
                } catch (Exception $e) {

                }
                break;

            case 'unsubscribed':
                try {
                    AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($storeId)
                        ->batchUnsubscribe($subscribers, $includeNames);
                } catch (Exception $e) {

                }
                break;

            default:
                break;
        }
        return ($page + 1);
    }

    /**
     *  Get subscribers for selected store, type, page
     *
     *  @param int $storeId
     *  @param string $type = 'subscribed'|'unsubscribed'|etc
     *  @param int $page
     *  @param int $pageSize
     *  @return array
     */
    private function getSubscribers($storeId, $type, $page, $pageSize)
    {
        $subscribersCollection = Mage::getModel('advancednewsletter/subscriber')
                ->getCollection()
                ->addStoreFilter($storeId)
        ;

        switch ($type) {
            case 'subscribed':
                $subscribersCollection->addFilterSubscribed();
                break;

            case 'unsubscribed':
                $subscribersCollection->addFilterUnsubscribed();
                break;

            default:
                break;
        }

        if ($subscribersCollection->getSize() < ($page - 1) * $pageSize) {
            return array();
        }

        $subscribers = $subscribersCollection
                ->setPageSize($pageSize)
                ->setCurPage($page);

        $arr = array();
        foreach ($subscribers as $subscriber) {
            if (!is_array($subscriber->getSegmentsCodes())) {
                $segments = explode(',', $subscriber->getSegmentsCodes());
                $subscriber->setSegmentsCodes($segments);
            }
            $arr[] = $subscriber;
        }
        return $arr;
    }

    public function removeOldStoredEmails()
    {
        Mage::getResourceModel('advancednewsletter/storedEmails')->removeOldStoredEmails();
        return $this;
    }
}
