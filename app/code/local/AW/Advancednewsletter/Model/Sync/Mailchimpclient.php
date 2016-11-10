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


class AW_Advancednewsletter_Model_Sync_Mailchimpclient implements AW_Advancednewsletter_Model_Sync_Interface
{
    /* Mailchimp configuration options */
    const MAILCHIMP_ENABLED = "advancednewsletter/mailchimpconfig/mailchimpenabled";
    const MAILCHIMP_AUTOSYNC = "advancednewsletter/mailchimpconfig/autosync";
    const MAILCHIMP_APIKEY = "advancednewsletter/mailchimpconfig/apikey";
    const MAILCHIMP_LISTID = "advancednewsletter/mailchimpconfig/listid";
    const MAILCHIMP_XMLRPC = "advancednewsletter/mailchimpconfig/xmlrpc";

    /*  XML-RPC request timeout */
    const REQUEST_TIMEOUT = 30;

    /* Grouping name on empty mailchimp list */
    const DEFAULT_GROUPING = 'ADVANCEDNEWSLETTER SYNCH GROUP';

    /**
     * Force autosync enable/disable variable
     * @var boolean
     */
    public static $disableAutosync = false;

    /**
     * Instance
     * @var AW_Advancednewsletter_Model_Sync_Mailchimpclient
     */
    protected static $_instance;
    protected $_client;
    protected $_clientStoreId;
    protected $_keysValues;
    protected $_segmentsLoaded = false;
    protected $_skipChangesCheck = false;
    protected $_includeNames = null;

    private function __construct()
    {

    }

    public function getSkipChangesCheck()
    {
        return $this->_skipChangesCheck;
    }

    public function setSkipChangesCheck($_skipChangesCheck)
    {
        $this->_skipChangesCheck = $_skipChangesCheck;
        return $this;
    }

    public function getIncludeNames()
    {
        return $this->_includeNames;
    }

    public function setIncludeNames($includeNames)
    {
        $this->_includeNames = $includeNames;
        return $this;
    }

    public function getStoreId()
    {
        return $this->_clientStoreId;
    }

    public static function getInstance($storeId)
    {
        $createNewInstance = true;
        if (self::$_instance) {
            if (self::$_instance->_client && self::$_instance->_clientStoreId == $storeId)
                $createNewInstance = false;
        }

        if ($createNewInstance) {
            $instance = new self;
            $instance->setKeys($storeId);
            try {
                $instance->_client = new Zend_XmlRpc_Client($instance->_connect());
                $instance->_client->getHttpClient()->setConfig(array('timeout' => self::REQUEST_TIMEOUT));
            } catch (Exception $e) {
                throw new AW_Advancednewsletter_Exception(
                    Mage::helper('advancednewsletter')->__('Couldn\'t connect to MailChimp')
                );
            }
            $instance->_clientStoreId = $storeId;
            self::$_instance = $instance;
        }

        return self::$_instance;
    }

    public static function testConnection($params)
    {
        $helper = Mage::helper('advancednewsletter');

        if (!isset($params['apikey'])) {
            throw new AW_Advancednewsletter_Exception($helper->__('Invalid Request. Api key info not found'));
        }

        if (!isset($params['xmlrpc'])) {
            throw new AW_Advancednewsletter_Exception($helper->__('Invalid Request. Xmlrpc info not found'));
        }

        $instance = new self();
        $instance->_keysValues[self::MAILCHIMP_APIKEY] = trim($params['apikey']);
        $instance->_keysValues[self::MAILCHIMP_XMLRPC] = trim($params['xmlrpc']);

        try {
            $client = new Zend_XmlRpc_Client($instance->_connect());
            $client->getHttpClient()->setConfig(array('timeout' => self::REQUEST_TIMEOUT));
            $client->call('chimpChatter', array($instance->_keysValues[self::MAILCHIMP_APIKEY]));
        } catch (Exception $e) {
             throw new AW_Advancednewsletter_Exception($helper->__('Cannot connect to MailChimp'));
        }
    }

    public function subscribe($subscriber)
    {
        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE) {
            return $this;
        }

        $merges = $this->checkAndGetMerges($subscriber);
        $this->loadSegments();
        $this->createGroupingOnEmptyList($merges);

        try {
            /*
             * Mailchimp API 1.2
             * listSubscribe(
             *     string apikey, string id, string email_address, array merge_vars, string email_type,
             *     boolean double_optin, boolean update_existing, boolean replace_interests, boolean send_welcome
             * )
             */
            $this->_client->call(
                'listSubscribe',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $subscriber->getData('email'),
                    $merges,
                    'html',
                    null,
                    true,
                    true,
                    false
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $ex) {
            throw new AW_Advancednewsletter_Exception($ex->getMessage());
        }

        return $this;
    }

    public function createGroupingOnEmptyList($merges)
    {
        try {
            $this->_client->call(
                'listInterestGroupings',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID]
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $e) {
            $interests = isset($merges['INTERESTS']) ? explode(',', $merges['INTERESTS']) : array('default');
            $this->_listInterestGroupingAdd($interests);
        }
    }

    protected function _logger()
    {
        return Mage::helper('awcore/logger');
    }

    public function unsubscribe($subscriber)
    {
        $subscriberSegments = $subscriber->getData('segments_codes');
        if (empty($subscriberSegments)) {
            $this->unsubscribeFromList($subscriber);
        } else {
            $this->subscribe($subscriber);
        }
        return $this;
    }

    public function unsubscribeFromList($subscriber)
    {
        try {
            /*
             * Mailchimp API 1.2
             * listUnsubscribe(
             *     string apikey, string id, string email_address, boolean delete_member,
             *     boolean send_goodbye, boolean send_notify
             * )
             */
            $this->_client->call(
                'listUnsubscribe',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $subscriber->getEmail(),
                    false,
                    false,
                    false
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $ex) {
            throw new AW_Advancednewsletter_Exception($ex->getMessage());
        }
        return $this;
    }

    public function delete($subscriber, $deleteFromOriginalData = false)
    {
        if ($deleteFromOriginalData) {
            $email = $subscriber->getOrigData('email');
        } else {
            $email = $subscriber->getData('email');
        }

        try {
            /*
             * Mailchimp API 1.2
             * listUnsubscribe(
             *     string apikey, string id, string email_address, boolean delete_member,
             *     boolean send_goodbye, boolean send_notify
             * )
             */
            $this->_client->call(
                'listUnsubscribe',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $email,
                    true,
                    false,
                    false
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $ex) {
            throw new AW_Advancednewsletter_Exception($ex->getMessage());
        }
        return $this;
    }

    public function loadSegments()
    {
        if (!$this->_segmentsLoaded) {
            $groups = array();
            $groupings = $this->getChimpGroupings();

            if ((isset($groupings[0]) && isset($groupings[0]['groups']))) {
                foreach ($groupings[0]['groups'] as $group) {
                    $groups[] = $group['name'];
                }
            } else {
                return false;
            }

            /* segments only for current store */
            $segments = Mage::getModel('advancednewsletter/segment')->getCollection();

            foreach ($segments as $segment) {
                if (count($groups)) {
                    if (!in_array($segment->getCode(), $groups)) {
                        $this->_listInterestGroupAdd($segment->getCode());
                    }
                } else {
                    $this->_listInterestGroupAdd($segment->getCode());
                }
            }
            $this->_segmentsLoaded = true;
        }
        return $this;
    }

    /**
     *   _listInterestGroupingAdd(string name, string type, array groups)
     *
     *      string name -  the interest grouping to add - grouping names must be unique
     *      string type -  The type of the grouping to add - one of "checkboxes", "hidden", "dropdown", "radio"
     *      array groups - The lists of initial group names to be added -
     *                      at least 1 is required and the names must be unique within a grouping.
     *                      If the number takes you over the 60 group limit, an error will be thrown.
     */
    protected function _listInterestGroupingAdd(
        $groups, $groupingName = self::DEFAULT_GROUPING, $groupingType = 'checkboxes'
    )
    {

        try {
            /*
             * API 1.3
             * listInterestGroupingAdd(string apikey, string id, string name, string type, array groups)
             */
            return $this->_client->call(
                'listInterestGroupingAdd',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $groupingName,
                    $groupingType,
                    $groups
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $e) {
             $this->_logger()->log(
                 $this,
                 sprintf(
                     "Failed to create grouping for list %s with error %s",
                     $this->_keysValues[self::MAILCHIMP_LISTID],
                     $e->getMessage()
                 )
             );
        }
        return false;
    }

    /**
     * listInterestGroupAdd(string apikey, string id, string group_name, int grouping_id)
     * Add a single Interest Group - if interest groups for the List are not yet enabled,
     * adding the first group will automatically turn them on.
     *  returns bool
     */
    protected function _listInterestGroupAdd($groupName)
    {
        $result = false;
        try {
            /*
             * API 1.3
             * listInterestGroupAdd(string apikey, string id, string group_name, int grouping_id)
             */
            $result = $this->_client->call(
                'listInterestGroupAdd',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $groupName
                )
            );
        } catch (Exception $exc) {
            // throw new AW_Advancednewsletter_Exception($exc->getMessage());
        }
        return $result;
    }

    public function removeSegment($segmentCode)
    {
        try {
            $this->_client->call(
                'listInterestGroupDel',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $segmentCode
                )
            );
        } catch (Zend_XmlRpc_Client_FaultException $exc) {
            // throw new AW_Advancednewsletter_Exception($exc->getMessage());
        }
    }

    public function getChimpGroupings()
    {
        $chimpGroupings = array();
        try {
            $chimpGroupings = $this->_client->call(
                'listInterestGroupings',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID]
                )
            );
        } catch (Exception $exc) {
            // throw new AW_Advancednewsletter_Exception($exc->getMessage());
        }
        return $chimpGroupings;
    }

    public function forceWrite($subscriber)
    {
        /**
         * If subscriber status = unsubscribed
         */
        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED) {
            /**
             * If subscribers store or email changed, remove subscriber from list and subscribe new one
             */
            if (
                    $subscriber->getOrigData('store_id') != $subscriber->getData('store_id') ||
                    $subscriber->getOrigData('email') != $subscriber->getData('email')
            ) {
                $instance = self::getInstance($subscriber->getOrigData('store_id'));
                $instance->delete($subscriber, true);
                $this->subscribe($subscriber->setIsNew(true));
            }
            $this->unsubscribeFromList($subscriber);
        }

        /**
         * If subscriber status = subscribed
         */
        if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
            $this->subscribe($subscriber);
        }
        return $this;
    }

    /*
     *   subscribed && unsubscribed ListMembers
     *
     */

    public function getRecords($membersType, $syncType, $pageNum, $pageSize)
    {
        $records = $this->_getListMembers($membersType, null, $pageNum, $pageSize);
        if ($syncType == AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_LIST) {

            $email = array();
            if (isset($records['data'])) {
                foreach ($records['data'] as $subscriber) {
                    $email[] = $subscriber['email'];
                }
            }
            $records = $this->_getlistMemberInfo($email);
        }
        return (isset($records['data'])) ? $records['data'] : array();
    }

    /**
     *   Get all of the list members for a list that are of a particular status.
     *
     *     getlistMembers(string status, string since, int pageNum, int pageSize)
     *      status - one of(subscribed, unsubscribed, cleaned, updated)
     */
    protected function _getListMembers($type='subscribed', $since=null, $pageNum=0, $pageSize=20)
    {
        $members = array();
        try {
            /*
             *  MailChimp API 1.3
             *    listMembers(string apikey, string id, string status, string since, int start, int limit)
             */
            $members = $this->_client->call(
                'listMembers',
                array(
                    $this->_keysValues[self::MAILCHIMP_APIKEY],
                    $this->_keysValues[self::MAILCHIMP_LISTID],
                    $type,
                    null,
                    $pageNum,
                    $pageSize
                )
            );
        } catch (Exception $exc) {
            // throw new AW_Advancednewsletter_Exception($exc->getMessage());
        }
        return $members;
    }

    /**
     *  Get all the information for particular members of a list
     *   input - an array of up to 50 email addresses to get information for
     *   OR the "id"(s) for the member returned from listMembers, Webhooks, and Campaigns
     *   returns  array
     */
    protected function _getlistMemberInfo($email)
    {
        $data = array();
        if (count($email)) {
            try {
                /*
                 * MailChimp API 1.3
                 * listMemberInfo(string apikey, string id, array email_address)
                 */
                $data = $this->_client->call(
                    'listMemberInfo',
                    array(
                        $this->_keysValues[self::MAILCHIMP_APIKEY],
                        $this->_keysValues[self::MAILCHIMP_LISTID],
                        $email
                    )
                );
            } catch (Exception $exc) {
                // throw new AW_Advancednewsletter_Exception($exc->getMessage());
            }
        }
        return (isset($data['data']) && count($data['data'])) ? $data : array();
    }

    protected function checkAndGetMerges($subscriber)
    {
        $merges = array();
        if (!$this->getSkipChangesCheck()) {
            $namesChanged = $segmentsChanged = $storeChanged = $emailChanged = false;

            if ($subscriber->getOrigData('store_id') != $subscriber->getData('store_id')) {
                $storeChanged = true;
            }
            if ($subscriber->getOrigData('email') != $subscriber->getData('email')) {
                $emailChanged = true;
            }
            if ($subscriber->getOrigData('first_name') != $subscriber->getData('first_name') ||
                    $subscriber->getOrigData('last_name') != $subscriber->getData('last_name')) {
                $namesChanged = true;
            }

            $subscriberOldSegments = array();
            if (is_array($subscriber->getOrigData('segments_codes'))) {
                $subscriberOldSegments = implode(',', $subscriber->getOrigData('segments_codes'));
            }
            $subscriberCurrentSegments = array();
            if (is_array($subscriber->getData('segments_codes'))) {
                $subscriberCurrentSegments = implode(',', $subscriber->getData('segments_codes'));
            }

            if ($subscriberOldSegments != $subscriberCurrentSegments) {
                $segmentsChanged = true;
            }

            if ($subscriber->getIsNew()) {
                $storeChanged = $emailChanged = false;
                $namesChanged = $segmentsChanged = true;
            }
            /*
             * If subscriber store id changed, we remove it from his previous list and set
             * $namesChanged and $segmentsChanged to true to upload this params to this customer
             * in the new list
             */
            if ($storeChanged || $emailChanged) {
                $instance = self::getInstance($subscriber->getOrigData('store_id'));
                $instance->delete($subscriber, true);
                $namesChanged = $segmentsChanged = true;
            }

            if ($segmentsChanged) {
                $merges['INTERESTS'] = $subscriberCurrentSegments;
            }

            if ($namesChanged) {
                $merges['FNAME'] = $subscriber->getData('first_name');
                $merges['LNAME'] = $subscriber->getData('last_name');
            }
        } else {
            $merges['INTERESTS'] = $subscriber->getData('segments_codes');
            if ($this->getIncludeNames()) {
                $merges['FNAME'] = $subscriber->getData('first_name');
                $merges['LNAME'] = $subscriber->getData('last_name');
            }
        }

        return $merges;
    }

    protected function setKeys($storeId)
    {
        $keys = array(
            self::MAILCHIMP_ENABLED,
            self::MAILCHIMP_AUTOSYNC,
            self::MAILCHIMP_APIKEY,
            self::MAILCHIMP_LISTID,
            self::MAILCHIMP_XMLRPC
        );

        /* AW_Advancednewsletter_Exception exceptions logs into aw_core_logger table, usual exceptions doesn't loging */
        $keysValues = Mage::helper('advancednewsletter')->getSettings($keys, $storeId, true);
        if (isset($keysValues[$storeId])) {
            $this->_keysValues = $keysValues[$storeId];
        } else {
            throw new AW_Advancednewsletter_Exception(Mage::helper('advancednewsletter')->__('Unknown Store Id'));
        }

        if (!$this->_keysValues[self::MAILCHIMP_APIKEY] || !$this->_keysValues[self::MAILCHIMP_XMLRPC]) {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Apikey or xmlrpc url are inncorrect')
            );
        }

        if (!$this->_keysValues[self::MAILCHIMP_ENABLED]) {
            throw new Exception(
                Mage::helper('advancednewsletter')->__('MailChimp is disabled for store %s', $storeId)
            );
        }

        if ((!$this->_keysValues[self::MAILCHIMP_AUTOSYNC]
                && AW_Advancednewsletter_Model_Cron::$massSyncFlag == false)
            || self::$disableAutosync
        ) {
            throw new Exception(
                Mage::helper('advancednewsletter')->__('MailChimp auto-sync is disabled for store %s', $storeId)
            );
        }
    }

    public function getKeys()
    {
        return $this->_keysValues;
    }

    protected function _connect()
    {
        $apiKey = $this->_keysValues[self::MAILCHIMP_APIKEY];
        $xmlRpcUrl = $this->_keysValues[self::MAILCHIMP_XMLRPC];

        if (!preg_match('/.*-us[0-9]+$/', $apiKey)) {
            throw new Exception;
        }

        list($key, $dc) = explode('-', $apiKey, 2);
        if (!$dc) {
            $dc = 'us1';
        }
        list($aux, $host) = explode('http://', $xmlRpcUrl);
        $apiHost = 'http://' . $dc . '.' . $host;
        return $apiHost;
    }

    /**
     * @param array $batch
     * @param bool $doubleOptin  - flag to control whether to send an opt-in confirmation email
     * @param bool $updateExisting - flag to control whether to update members
     *                               that are already subscribed to the list or to return an error
     * @param bool $replaceInterests - flag to determine whether we replace the interest groups with the updated
     *                               groups provided, or we add the provided groups to the member's
     *                               interest groups (optional, defaults to true)
     *
     * @return array $data - response from MailChimp
     */
    public function batchSubscribe($batch, $doubleOptin = FALSE, $updateExisting = TRUE, $replaceInterests = TRUE)
    {
        $this->loadSegments();

        $data = array();
        if (count($batch)) {
            try {

                $this->createGroupingOnEmptyList($batch);
                /*
                 * MailChimp API 1.3
                 * listBatchSubscribe(
                 *     string apikey, string id, array batch, boolean double_optin,
                 *     boolean update_existing, boolean replace_interests
                 * )
                 */
                $data = $this->_client->call(
                    'listBatchSubscribe',
                    array(
                        $this->_keysValues[self::MAILCHIMP_APIKEY],
                        $this->_keysValues[self::MAILCHIMP_LISTID],
                        $batch,
                        /* flag to control whether to send an opt-in confirmation email - defaults to true */
                        $doubleOptin,
                        /* flag to control whether to update members that are already subscribed to the list
                        or to return an error, defaults to false (return error) */
                        $updateExisting,
                        /* flag to determine whether we replace the interest groups with the updated groups provided,
                         * or we add the provided groups to the member's interest groups (optional, defaults to true) */
                        $replaceInterests,
                    )
                );

            } catch (Exception $exc) {
                // $exc->getMessage();
            }
        }
        return $data;
    }

    /**
     *  Exec 'listBatchUnsubscribe' at MailChimp server
     *
     *  @param array $email
     *  @param bool $deleteMembers
     *  @param bool $sendGoodbye
     *  @param bool $sendNotify
     *
     *  @return array $data  response form MailChimp
     */
    public function batchUnsubscribe($email, $deleteMembers = FALSE, $sendGoodbye = FALSE, $sendNotify = FALSE)
    {
        $data = array();
        if (count($email)) {
            try {
                /*
                 * listBatchUnsubscribe ??? v1.3
                 *  listBatchUnsubscribe(
                 *   string apikey, string id, array emails,
                 *   boolean delete_member, boolean send_goodbye, boolean send_notify
                 * )
                 */
                $data = $this->_client->call(
                    'listBatchUnsubscribe',
                    array(
                        $this->_keysValues[self::MAILCHIMP_APIKEY],
                        $this->_keysValues[self::MAILCHIMP_LISTID],
                        $email,
                        $deleteMembers,
                        $sendGoodbye,
                        $sendNotify
                    )
                );
            } catch (Exception $exc) {
                //echo $exc->getMessage();
            }
        }
        return $data;
    }

}