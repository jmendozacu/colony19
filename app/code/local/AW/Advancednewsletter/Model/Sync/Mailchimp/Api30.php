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


class AW_Advancednewsletter_Model_Sync_Mailchimp_Api30
    implements AW_Advancednewsletter_Model_Sync_Mailchimp_ApiInterface
{
    /**
     * Mailchimp statuses
     */
    const MAILCHIMP_STATUS_SUBSCRIBED   = 'subscribed';
    const MAILCHIMP_STATUS_UNSUBSCRIBED = 'unsubscribed';

    /**
     * Default group name for empty MailChimp list
     */
    const DEFAULT_GROUP_NAME = 'ADVANCEDNEWSLETTER SYNCH GROUP';

    /**
     * Mailchimp List ID
     *
     * @var string
     */
    private $listId;

    /**
     * @var AW_Advancednewsletter_Model_Sync_Mailchimp_Api30Wrapper
     */
    private $mailchimpWrapper;

    /**
     * @var array
     */
    private $listSegments;

    /**
     * @param string $apiKey
     * @param string $listId
     * @throws AW_Advancednewsletter_Exception
     */
    function __construct($apiKey, $listId)
    {
        $this->mailchimpWrapper = new AW_Advancednewsletter_Model_Sync_Mailchimp_Api30Wrapper($apiKey);
        $this->listId = $listId;
        try {
            $result = $this->mailchimpWrapper->get("lists/{$this->listId}");
        } catch (Exception $e) {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Cannot connect to MailChimp')
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function subscribeToList($email, $firstName, $lastName, $segments)
    {
        $this->validateList();

        $subscriberHash = $this->mailchimpWrapper->getSubscriberHash($email);

        $listSegmentsToUpdate = $this->getSegmentsToUpdate($segments);

        $this->mailchimpWrapper->put(
            "lists/{$this->listId}/members/{$subscriberHash}",
            array (
                'email_address' => $email,
                'status_if_new' => self::MAILCHIMP_STATUS_SUBSCRIBED,
                'status'        => self::MAILCHIMP_STATUS_SUBSCRIBED,
                'email_type'    => 'html',
                'interests'     => $listSegmentsToUpdate,
                'merge_fields'  => array (
                    'FNAME'     => $firstName,
                    'LNAME'     => $lastName
                )
            )
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribeFromList($email, $firstName, $lastName, $segments)
    {
        $this->validateList();

        $listSegmentsToUpdate = $this->getSegmentsToUpdate($segments);

        $subscriberHash = $this->mailchimpWrapper->getSubscriberHash($email);
        $this->mailchimpWrapper->patch(
            "lists/{$this->listId}/members/{$subscriberHash}",
            array (
                'status' => self::MAILCHIMP_STATUS_UNSUBSCRIBED,
                'interests'     => $listSegmentsToUpdate,
                'merge_fields'  => array (
                    'FNAME'     => $firstName,
                    'LNAME'     => $lastName
                )
            )
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFromList($email)
    {
        $this->validateList();

        $subscriberHash = $this->mailchimpWrapper->getSubscriberHash($email);
        $this->mailchimpWrapper->delete(
            "lists/{$this->listId}/members/{$subscriberHash}"
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribersFromList($status, $page = 0, $pageSize = 20)
    {
        $this->validateList();

        $result = $this->mailchimpWrapper->get(
            "lists/{$this->listId}/members",
            array (
                'status'    => $status,
                'count'     => $pageSize,
                'offset'    => $page * $pageSize
            )
        );

        $subscribers = array();
        foreach ($result['members'] as $member) {
            if (isset($member['merge_fields'])) {
                $mergeFields = $member['merge_fields'];
            }
            $segments = array();
            if (isset($member['interests'])) {
                $interests = $member['interests'];
                foreach ($interests as $id => $value) {
                    if ($value == true) {
                        $segment = $this->getSegmentName($id);
                        if ($segment) {
                            $segments[] = $segment;
                        }
                    }
                }
            }
            $subscriber = array(
                'email'         => $member['email_address'],
                'status'        => $member['status'],
                'first_name'    => isset($mergeFields['FNAME']) ? $mergeFields['FNAME'] : '',
                'last_name'     => isset($mergeFields['LNAME']) ? $mergeFields['LNAME'] : '',
                'segments'      => $segments
            );
            $subscribers[] = $subscriber;
        }
        return $subscribers;
    }

    /**
     * {@inheritDoc}
     */
    public function getSegmentsFromList()
    {
        $this->validateList();

        $segments = array();
        $groupId = $this->getGroupId();

        $result = $this->mailchimpWrapper->get(
            "lists/{$this->listId}/interest-categories/{$groupId}/interests"
        );

        if (isset($result['interests'])) {
            foreach ($result['interests'] as $interest) {
                $segments[$interest['id']] = $interest['name'];
            }
        }

        return $segments;
    }

    /**
     * {@inheritDoc}
     */
    public function addSegmentToList($segment)
    {
        $this->validateList();

        $groupId = $this->getGroupId();

        $this->mailchimpWrapper->post(
            "lists/{$this->listId}/interest-categories/{$groupId}/interests",
            array (
                'name' => $segment
            )
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeSegmentFromList($segment)
    {
        $this->validateList();

        $groupId = $this->getGroupId();

        $listSegments = $this->getSegmentsFromList();
        foreach ($listSegments as $listSegmentId => $listSegmentName) {
            if ($listSegmentName == $segment) {
                $this->mailchimpWrapper->delete(
                    "lists/{$this->listId}/interest-categories/{$groupId}/interests/{$listSegmentId}"
                );
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLists()
    {
        $result = $this->mailchimpWrapper->get("lists");

        $lists = array();
        if (isset($result['lists'])) {
            foreach ($result['lists'] as $list) {
                $lists[$list['id']] = $list['name'];
            }
        }

        return $lists;
    }

    /**
     * {@inheritDoc}
     */
    public function batchSubscribe($subscribers, $updateNames = true)
    {
        $this->validateList();

        $operations = array();
        foreach ($subscribers as $subscriber) {
            $subscriberHash = $this->mailchimpWrapper->getSubscriberHash($subscriber['email']);

            $listSegmentsToUpdate = $this->getSegmentsToUpdate($subscriber['segments']);

            $subscriberOperation = array(
                'email_address' => $subscriber['email'],
                'status_if_new' => self::MAILCHIMP_STATUS_SUBSCRIBED,
                'status'        => self::MAILCHIMP_STATUS_SUBSCRIBED,
                'email_type'    => 'html',
                'interests'     => $listSegmentsToUpdate
            );
            if ($updateNames) {
                $subscriberOperation['merge_fields'] = array (
                    'FNAME'     => $subscriber['first_name'],
                    'LNAME'     => $subscriber['last_name']
                );
            }
            $operations[] = array(
                'method'    => 'PUT',
                'path'      => "lists/{$this->listId}/members/{$subscriberHash}",
                'body'      =>  json_encode($subscriberOperation)
            );
        }

        $this->mailchimpWrapper->post(
            "batches",
            array(
                'operations' => $operations
            )
        );

        return $this;
    }

    public function batchUnsubscribe($subscribers, $updateNames = true)
    {
        $this->validateList();

        $operations = array();
        foreach ($subscribers as $subscriber) {
            $subscriberHash = $this->mailchimpWrapper->getSubscriberHash($subscriber['email']);
            $listSegmentsToUpdate = $this->getSegmentsToUpdate($subscriber['segments']);
            $subscriberOperation = array(
                'email_address' => $subscriber['email'],
                'status_if_new' => self::MAILCHIMP_STATUS_UNSUBSCRIBED,
                'status'        => self::MAILCHIMP_STATUS_UNSUBSCRIBED,
                'email_type'    => 'html',
                'interests'     => $listSegmentsToUpdate
            );
            if ($updateNames) {
                $subscriberOperation['merge_fields'] = array (
                    'FNAME'     => $subscriber['first_name'],
                    'LNAME'     => $subscriber['last_name']
                );
            }
            $operations[] = array(
                'method'    => 'PUT',
                'path'      => "lists/{$this->listId}/members/{$subscriberHash}",
                'body'      =>  json_encode($subscriberOperation)
            );
        }

        $this->mailchimpWrapper->post(
            "batches",
            array(
                'operations' => $operations
            )
        );

        return $this;
    }

    /**
     * Get id of the synchronization group
     * If there is no group in the current list group with default name will be created
     *
     * @return string
     * @throws AW_Advancednewsletter_Exception
     */
    private function getGroupId()
    {
        $result = $this->mailchimpWrapper->get(
            "lists/{$this->listId}/interest-categories"
        );

        if (isset($result['categories']) && isset($result['categories'][0])) {
            $firstInterestGroup = $result['categories'][0];
        } else {
            $defaultGroupId = $this->addDefaultGroupToList();
            if ($defaultGroupId) {
                $firstInterestGroup['id'] = $defaultGroupId;
            } else {
                throw new AW_Advancednewsletter_Exception(
                    Mage::helper('advancednewsletter')->__(
                        'Can not create an empty group in MailChimp list. ' .
                        'Please create it manually (name of the group doesn\'t matter)'
                    )
                );
            }

        }
        return $firstInterestGroup['id'];
    }

    /**
     * Add default group to current MailChimp list
     *
     * @return string groupId|false;
     * @throws AW_Advancednewsletter_Exception
     */
    private function addDefaultGroupToList()
    {
        $result = $this->mailchimpWrapper->post(
            "lists/{$this->listId}/interest-categories",
            array(
                'title' => self::DEFAULT_GROUP_NAME,
                'type'  => 'checkboxes'
            )
        );

        if (isset($result['id'])) {
            return $result['id'];
        }

        return false;
    }

    /**
     * Get segments to update on the MailChimp side
     *
     * @param array $segments
     * @return array
     */
    private function getSegmentsToUpdate($segments)
    {
        if (!$this->listSegments) {
            $this->listSegments = $this->getSegmentsFromList();
        }

        $listSegmentsToUpdate = array();
        foreach ($this->listSegments as $listSegmentId => $listSegmentName) {
            $listSegmentsToUpdate[$listSegmentId] = false;
        }

        foreach ($segments as $segment) {
            foreach ($this->listSegments as $listSegmentId => $listSegmentName) {
                if ($segment == $listSegmentName) {
                    $listSegmentsToUpdate[$listSegmentId] = true;
                    break;
                }
            }
        }

        return $listSegmentsToUpdate;
    }

    /**
     * Get segment name
     *
     * @param $segmentId
     * @return string|false;
     */
    private function getSegmentName($segmentId)
    {
        $listSegments = $this->getSegmentsFromList();

        if (isset($listSegments[$segmentId])) {
            return $listSegments[$segmentId];
        }

        return false;
    }

    /**
     * Validate list id
     *
     * @return void
     * @throws AW_Advancednewsletter_Exception
     */
    private function validateList()
    {
        if (is_null($this->listId )|| $this->listId == '') {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('List ID is not valid')
            );
        }
    }
}
