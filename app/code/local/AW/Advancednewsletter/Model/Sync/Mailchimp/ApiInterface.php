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


interface AW_Advancednewsletter_Model_Sync_Mailchimp_ApiInterface
{
    /**
     * Subscribe to current MailChimp list
     *
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param array $segments
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function subscribeToList($email, $firstName, $lastName, $segments);


    /**
     * Unsubscribe from current MailChimp list
     *
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param array $segments
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function unsubscribeFromList($email, $firstName, $lastName, $segments);

    /**
     * Delete subscriber from current MailChimp list
     *
     * @param string $email
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function deleteFromList($email);

    /**
     * Get subscribers from current MailChimp list
     *
     * @param string $status ('subscribed', 'unsubscribed', 'cleaned', 'pending')
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getSubscribersFromList($status, $page, $pageSize);

    /**
     * Get segments from current MailChimp list (array('id' => 'name'))
     *
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function getSegmentsFromList();

    /**
     * Add segment to current MailChimp list
     * If there is no group in the current list group with default name will be created
     *
     * @param string $segment
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function addSegmentToList($segment);

    /**
     * Remove segment from current MailChimp list
     *
     * @param string $segment
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function removeSegmentFromList($segment);

    /**
     * Get all MailChimp lists
     *
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function getAllLists();

    /**
     * Batch subscribe
     *
     * @param array $subscribers (array(array('email'=>.., 'segments'=>.., 'first_name'=>.., 'last_name'=>..)))
     * @param bool $updateNames
     * @return $this
     */
    public function batchSubscribe($subscribers, $updateNames = true);

    /**
     * Batch unsubscribe
     *
     * @param array $subscribers (array(array('email'=>.., 'segments'=>.., 'first_name'=>.., 'last_name'=>..)))
     * @param bool $updateNames
     * @return $this
     */
    public function batchUnsubscribe($subscribers, $updateNames = true);
}
