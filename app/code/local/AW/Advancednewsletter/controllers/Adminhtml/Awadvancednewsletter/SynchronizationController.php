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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_SynchronizationController extends Mage_Adminhtml_Controller_Action
{
    CONST OLD_NEWSLETTER_SEGMENT = 'old_newsletter_subscriber';

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Synchronization'));
        }
        return $this;
    }

    public function indexAction()
    {
        $this
            ->displayTitle()
            ->loadLayout()
            ->_setActiveMenu('advancednewsletter')
            ->renderLayout()
        ;
    }

    public function editAction()
    {
        $this
            ->displayTitle()
            ->loadLayout()
            ->_setActiveMenu('advancednewsletter')
            ->renderLayout()
        ;
    }

    public function testConnectionAction()
    {
        $apiKey = $this->getRequest()->getParam('apikey');
        try {
            if (!$apiKey) {
                throw new AW_Advancednewsletter_Exception(
                    $this->_helper()->__('Invalid Request. Api key info not found')
                );
            }
            AW_Advancednewsletter_Model_Sync_Mailchimp::testConnection($apiKey);
        } catch (Exception $e) {
            return $this->getResponse()->setBody($this->_helper()->__($e->getMessage()));
        }
        return $this->getResponse()->setBody($this->_helper()->__('Connection successfull'));
    }

    public function syncAction()
    {
        $helper = Mage::helper('advancednewsletter');
        $request = $this->getRequest();
        $syncType = $this->getRequest()->getParam('type');

        $stores = array();
        foreach (array_keys(Mage::app()->getStores()) as $storeId) {
            $stores[$storeId] = array(
                'subscr_page' => 0,
                'unsubscr_page' => 0,
            );
        }

        switch ($syncType) {

            case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_TO_MAILCHIMP:

                $syncFor = $request->getParam('sync_for');
                $syncPages = array(
                    'subscr_page' => 1,
                    'unsubscr_page' => 1,
                    'include_names' => (bool) $request->getParam('include_names'),
                );

                switch ($syncFor) {
                    case 'subscribed':
                        $syncPages['unsubscr_page'] = 'none';
                        break;

                    case 'unsubscribed':
                        $syncPages['subscr_page'] = 'none';
                        break;

                    default:
                        break;
                }

                foreach ($stores as $storeId => $value) {
                    $stores[$storeId] = $syncPages;
                }

                Mage::getModel('advancednewsletter/cache')
                    ->saveCache(serialize($stores), 'aw_advancednewsletter_mailchimp_to_params')
                ;
                $this->_addQueueDetailMessage();
                break;


            case AW_Advancednewsletter_Block_Adminhtml_Synchronization::SYNC_FROM_MAILCHIMP:
                $syncFor = $request->getParam('sync_for');
                foreach ($stores as $storeId => $value) {
                    $stores[$storeId]['sync_for'] = $syncFor;
                }
                Mage::getModel('advancednewsletter/cache')
                    ->saveCache(serialize($stores), 'aw_advancednewsletter_mailchimp_from_params')
                ;
                $this->_addQueueDetailMessage();
                break;


            default:
                Mage::getModel('adminhtml/session')->addError($helper->__('Choose needed parameters'));
                break;
        }
        return $this->_redirect('*/*/edit', array('type' => $syncType));
    }

    protected function _addQueueDetailMessage()
    {
        $total = $queued = 0;
        foreach (Mage::app()->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            ++$total;

            if ($this->_helper()->isChimpEnabled($store->getId())) {
                ++$queued;
            }
        }

        if (!$queued) {
            Mage::getSingleton('adminhtml/session')->addNotice(
                $this->_helper()->__('Synchronization is disabled for all stores. Please check configuration settings.')
            );
        } else {
            Mage::getSingleton('adminhtml/session')->addSuccess(
                $this->_helper()->__('Synchronization was queued for %d of total %d stores', $queued, $total)
            );
        }

        return $this;
    }

    public function newsletterSubscribersImportAction()
    {
        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('log_write');
        $subscriberTable = $resource->getTableName("advancednewsletter/subscriber");

        if (Mage::helper('advancednewsletter')->magentoLess14()) {
            $newsletterSubscribersCollection = Mage::getResourceModel('newsletter/subscriber_collection');
        } else {
            $newsletterSubscribersCollection = Mage::getModel('newsletter/subscriber')->getCollection();
        }
        $newsletterSubscribersCollection->showCustomerInfo();
        Mage::getModel('advancednewsletter/segment')->createNewSegment(self::OLD_NEWSLETTER_SEGMENT);
        foreach ($newsletterSubscribersCollection as $subscriber) {
            $newStatus = 0;
            switch ($subscriber->getStatus()) {
                case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                    $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                    break;
                case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
                    $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE;
                    break;
                case Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                    $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                    break;
            }

            try {
                $data = array();
                $data['store_id'] = $subscriber->getStoreId();
                if ($subscriber->getCustomerId()) {
                    $data['customer_id'] = $subscriber->getCustomerId();
                    $data['first_name'] = $subscriber->getCustomerFirstname();
                    $data['last_name'] = $subscriber->getCustomerLastname();
                }
                $data['email'] = $subscriber->getSubscriberEmail();
                $data['status'] = $newStatus;
                $data['confirm_code'] = $subscriber->getSubscriberConfirmCode();
                $data['segments_codes'] = self::OLD_NEWSLETTER_SEGMENT;

                $sql = sprintf(
                    "INSERT IGNORE INTO %s (%s) VALUES('%s')",
                    $subscriberTable,
                    implode(',', array_keys($data)),
                    implode("','", $data)
                );
                $connWrite->query($sql);
            } catch (Exception $ex) {

            }
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('adminhtml')->__('Subscribers synchronization successfully completed')
        );
        return $this->_redirect('*/*/index');
    }

    public function newsletterTemplatesImportAction()
    {
        $newsletterTemplatesCollection = Mage::getModel('newsletter/template')->getCollection();
        Mage::getModel('advancednewsletter/segment')->createNewSegment(self::OLD_NEWSLETTER_SEGMENT);
        foreach ($newsletterTemplatesCollection as $template) {
            try {
                Mage::getModel('advancednewsletter/template')
                        ->addData($template->getData())
                        ->setId(null)
                        ->setSegmentsCodes(array(self::OLD_NEWSLETTER_SEGMENT))
                        ->save();
            } catch (Exception $ex) {

            }
        }
        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('adminhtml')->__('Templates synchronization successfully completed')
        );
        return $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/synchronization');
    }

    protected function _helper()
    {
        return Mage::helper('advancednewsletter');
    }

}