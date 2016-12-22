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


interface AW_Advancednewsletter_Model_Sync_Interface
{
    /**
     * Subscribe a subscriber
     *
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function subscribe($subscriber);

    /**
     * Unsubscribe a subscriber
     *
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function unsubscribe($subscriber);

    /**
     * Delete a subscriber
     *
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @param bool $useOriginalData
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function delete($subscriber, $useOriginalData = false);

    /**
     * Rewrite a subscriber by new data
     *
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function forceWrite($subscriber);

    /**
     * Remove segment
     *
     * @param string $segmentCode
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function removeSegment($segmentCode);

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
     * @param array $subscribers
     * @param bool $updateNames
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function batchSubscribe($subscribers, $updateNames = TRUE);

    /**
     * Batch unsubscribe
     *
     * @param array $subscribers
     * @param bool $updateNames
     * @return $this
     * @throws AW_Advancednewsletter_Exception
     */
    public function batchUnsubscribe($subscribers, $updateNames = true);

    /**
     * Get all segments
     *
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function getSegments();

    /**
     * Get subscribers
     *
     * @param string $status
     * @param int $page
     * @param int $pageSize
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function getSubscribers($status, $page, $pageSize);
}
