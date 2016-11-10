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
     *
     *    listMemberInfo - up to 50 emails, listMembers up to 15000.  list_sync uses listMemberInfo
     */
    const SYNC_FROM_MAILCHIMP_PAGE_SIZE = 45;
    const SYNC_FROM_MAILCHIMP_PAGE_SIZE_2 = 450;


    public static $massSyncFlag = false;
    protected $_syncFromType;


    /*
     *   Name for export settings
     */
    const SYNC_TO_PARAMS_NAME = 'aw_advancednewsletter_mailchimp_to_params';
    /*
     *   Name for import settings
     */
    const SYNC_FROM_PARAMS_NAME = 'aw_advancednewsletter_mailchimp_from_params';

    /*
     *    config params
     */
    const MAILCHIMP_ENABLED = "advancednewsletter/mailchimpconfig/mailchimpenabled";
    const MAILCHIMP_AUTOSYNC = "advancednewsletter/mailchimpconfig/autosync";
    const MAILCHIMP_APIKEY = "advancednewsletter/mailchimpconfig/apikey";
    const MAILCHIMP_LISTID = "advancednewsletter/mailchimpconfig/listid";
    const MAILCHIMP_XMLRPC = "advancednewsletter/mailchimpconfig/xmlrpc";

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

    protected function _checkInstanceDuplicate($instances, $instanceForCheck)
    {
        $instanceForCheckKeys = $instanceForCheck->getKeys();
        foreach ($instances as $instance) {
            $instanceKeys = $instance->getKeys();
            $apiKey = AW_Advancednewsletter_Model_Sync_Mailchimpclient::MAILCHIMP_APIKEY;
            $listId = AW_Advancednewsletter_Model_Sync_Mailchimpclient::MAILCHIMP_LISTID;
            if ($instanceKeys[$apiKey] == $instanceForCheckKeys[$apiKey]
                && $instanceKeys[$listId] == $instanceForCheckKeys[$listId]) {
                return true;
            }
        }
        return false;
    }

    /**
     * Import data from MailChimp to store
     *
     * @param $records
     * @param $storeId
     * @param $syncFor
     * @param $status
     */
    protected function _addRecordsToStore($records, $storeId, $syncFor, $status)
    {
        foreach ($records as $record) {
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($record['email']);
            switch ($syncFor) {
                case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_STATUSES:
                    if ($subscriber->getId()) {
                        $subscriber->setStatus($status);
                    }

                    break;
                case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_LIST:
                    // TODO: Throw exception
                    if (!isset($record['merges']))
                        continue;
                    $groups = array();
                    foreach ($record['merges']['GROUPINGS'] as $grouping) {
                        $segmentsInGrouping = explode(', ', $grouping['groups']);
                        $groups = array_merge($segmentsInGrouping, $groups);
                    }
                    $groups = array_unique($groups);
                    foreach ($groups as $keyGroup => $oneGroup) {
                        if (empty($oneGroup))
                            unset($groups[$keyGroup]);
                    }

                    $email = $record['merges']['EMAIL'];
                    $customerByEmail = Mage::getModel('customer/customer')
                        ->setStore(Mage::app()->getStore($storeId))
                        ->loadByEmail($email)
                    ;
                    if ($customerByEmail->getId())
                        $subscriber->setCustomerId($customerByEmail->getId());

                    $subscriber
                            ->setStatus($status)
                            ->setEmail($email)
                            ->setFirstName($record['merges']['FNAME'])
                            ->setLastName($record['merges']['LNAME'])
                            ->setSegmentsCodes($groups)
                            ->setStoreId($storeId);

                    break;
                default:
                    break;
            }
            /* save customer */
            try {
                $subscriber->save();
            } catch (Exception $ex) {

            }
        }
    }

    /**
     *  Import data from MailChimp
     */
    public function syncFromMailchimp()
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }
        /*
         *  TODO:  use different pagesize for sync_list  & sync_statuses
         *    listMemberInfo - up to 50 emails, listMembers up to 15000.  list_sync uses listMemberInfo
         */
        self::$massSyncFlag = true;
        $cache = Mage::getModel('advancednewsletter/cache');
        $syncFromParams = $cache->loadCache(self::SYNC_FROM_PARAMS_NAME);
        if (!$syncFromParams)
            return;
        $syncFromParams = unserialize($syncFromParams);

        if (!count($syncFromParams)) {
            return;
        }

        /* Getting instances for sync. Instances with the same apikey and listid joined to one */
        $instances = array();
        foreach ($syncFromParams as $storeId => $storePages) {
            try {
                $instanceForCheck = AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($storeId);
                if (!$this->_checkInstanceDuplicate($instances, $instanceForCheck))
                    $instances[] = $instanceForCheck;
            } catch (Exception $ex) {
                continue;
            }
        }

        /* Creation segments for all instances */
        foreach ($instances as $instance) {
            try {
                $chimpGroupings = $instance->getChimpGroupings();
                foreach ($chimpGroupings as $chimpGrouping) {
                    $arr = array();
                    foreach ($chimpGrouping['groups'] as $segment) {
                        $arr[] = $segment['name'];
                    }
                    Mage::getModel('advancednewsletter/segment')->massCreation($arr);
                }
            } catch (Exception $ex) {
                continue;
            }
        }

        $instancesIds = array();
        /* Subscrubers sync */
        foreach ($instances as $instance) {

            $storeId = $instance->getStoreId();
            $syncFor = $syncFromParams[$storeId]['sync_for'];

            $pageSize = self::SYNC_FROM_MAILCHIMP_PAGE_SIZE;
            if ($syncFor === 'sync_statuses') {
                $pageSize = self::SYNC_FROM_MAILCHIMP_PAGE_SIZE_2;
            }

            $instancesIds[] = $storeId;
            $page = $syncFromParams[$storeId]['subscr_page'];
            if (!($page === 'none')) {
                $subscribers = $instance->getRecords('subscribed', $syncFor, $page, $pageSize);
                if (count($subscribers)) {
                    $this->_addRecordsToStore(
                        $subscribers, $storeId, $syncFor, AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED
                    );
                    ++$syncFromParams[$storeId]['subscr_page'];
                } else {
                    $syncFromParams[$storeId]['subscr_page'] = 'none';
                }
            }


            $page = $syncFromParams[$storeId]['unsubscr_page'];
            if (!($page === 'none')) {
                $unsubscribers = $instance->getRecords('unsubscribed', $syncFor, $page, $pageSize);
                if (count($unsubscribers)) {
                    $this->_addRecordsToStore(
                        $unsubscribers, $storeId, $syncFor, AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
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
        self::$massSyncFlag = false;
    }

    /**
     *  Sync to MailChimp
     */
    public function syncToMailchimp()
    {
        if (!Mage::helper('advancednewsletter')->isChimpEnabled()) {
            return $this;
        }

        self::$massSyncFlag = true;
        $cache = Mage::getModel('advancednewsletter/cache');
        $syncToParams = $cache->loadCache(self::SYNC_TO_PARAMS_NAME);
        if (!$syncToParams) {
            return;
        }

        $syncToParams = unserialize($syncToParams);
        foreach ($syncToParams as $storeId => $pagesToSync) {
            if (
                Mage::getStoreConfig(self::MAILCHIMP_ENABLED, $storeId)
                && Mage::getStoreConfig(self::MAILCHIMP_APIKEY, $storeId)
                && Mage::getStoreConfig(self::MAILCHIMP_LISTID, $storeId)
            ) {

                // sync  subspage
                $page = $syncToParams[$storeId]['subscr_page'];
                $syncToParams[$storeId]['subscr_page'] = $this->_exportSubsPage(
                    $storeId, 'subscribed', $page, self::SYNC_TO_MAILCHIMP_PAGE_SIZE, $pagesToSync['include_names']
                );

                // sync  unsubspage
                $page = $syncToParams[$storeId]['unsubscr_page'];
                $syncToParams[$storeId]['unsubscr_page'] = $this->_exportSubsPage(
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

        self::$massSyncFlag = false;
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

    protected function _exportSubsPage($storeId, $type, $page, $pageSize, $includeNames)
    {
        if ($page === 'none') {
            return 'none';
        }

        $subscribers = $this->_getSubscibersPack($storeId, $type, $page, $pageSize);

        if (!count($subscribers)) {
            return 'none';
        }

        switch ($type) {
            case 'subscribed':
                $batch = $this->_getSubsBatch($subscribers, TRUE);
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($storeId)->batchSubscribe(
                    $batch, false, $includeNames
                );
                break;

            case 'unsubscribed':
                /*
                 *   1) unsubscribe batch with 'delete_members' to delete old data
                 *   2) subscribe batch to list to add new data
                 *   3) unsubscribe this batch to change status
                 */

                $batch = $this->_getSubsBatch($subscribers);

                $email = array();
                foreach ($batch as $subscriber) {
                    $email[] = $subscriber['EMAIL'];
                }
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($storeId)
                    ->batchUnsubscribe($email, TRUE)
                ;
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($storeId)
                    ->batchSubscribe($batch)
                ;
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::getInstance($storeId)
                    ->batchUnsubscribe($email, FALSE)
                ;
                break;

            default:
                break;
        }
        return ($page + 1);
    }

    /**
     * Create formatted batch array for mailchimp request
     * @param array $subscribers
     * @param bool $includeInterests Include FNAME && LNAME
     *
     * @return array
     */
    private function _getSubsBatch($subscribers, $includeInterests = FALSE)
    {
        $batch = array();
        foreach ($subscribers as $subscriber) {
            $subsData = array('EMAIL' => $subscriber['email']);
            if ($includeInterests) {
                $subsData['INTERESTS'] = $subscriber['segments_codes'];
            }
            $subsData['FNAME'] = $subscriber['first_name'];
            $subsData['LNAME'] = $subscriber['last_name'];
            $batch[] = $subsData;
        }

        return $batch;
    }

    /**
     *  Get subscribers data for selected store, type, page
     *
     *  @param int $storeId
     *  @param string $type = 'subscribed'|'unsubscribed'|etc
     *  @param int $page
     *  @param int $pageSize
     *
     *  @return array
     */
    protected function _getSubscibersPack($storeId, $type, $page, $pageSize)
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
            $arr[] = $subscriber->getData();
        }
        return $arr;
    }

    public function removeOldStoredEmails()
    {
        Mage::getResourceModel('advancednewsletter/storedEmails')->removeOldStoredEmails();
        return $this;
    }
}