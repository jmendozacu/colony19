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


class AW_Advancednewsletter_Model_Observer
{
    /**
     * updating names for subscriber after customer names update
     * @param Mage_Customer_Model_Customer $customer 
     */
    protected function updateNames($customer)
    {
        $subscriber = Mage::getModel('advancednewsletter/subscriber')->load($customer->getEntityId(), 'customer_id');
        if (!$subscriber->getId()) {
            return;
        }

        if ($subscriber->getData('last_name') != $customer->getLastname()
            || $subscriber->getData('first_name') != $customer->getFirstname()) {
            $subscriber->forceWrite(
                array(
                    'first_name' => $customer->getFirstname(),
                    'last_name' => $customer->getLastname()
                )
            );
        }
        if ($subscriber->getEmail() != $customer->getEmail()) {
            $duplicate = Mage::getModel('advancednewsletter/subscriber')->load($customer->getEmail(), 'email');
            if (!$duplicate->getId()) {
                $subscriber->forceWrite(
                    array(
                        'email' => $customer->getEmail(),
                    )
                );
            }
        }
    }

    /**
     * Subscribe customer after registration
     * @param Mage_Customer_Model_Customer $customer 
     */
    protected function subscribeAfterRegistration($customer)
    {
        if (Mage::helper('advancednewsletter')->showSegmentsAtCustomerRegistration()) {
            $subscribedSegments = Mage::app()->getRequest()->getParam('segments_select');
            if (!empty($subscribedSegments)) {
                $customer->setIsSubscribed(true);
                try {
                    Mage::getModel('advancednewsletter/subscriber')
                        ->setCustomer($customer)
                        ->subscribe($customer->getEmail(), $subscribedSegments)
                    ;
                } catch (Exception $ex) {
                    
                }
            }
        } else if ($customer->getIsSubscribed()) {
            $segmentsToSubscribe = array();
            $segmentsCollection = Mage::getModel('advancednewsletter/segment')
                    ->getCollection()
                    ->addFieldToFilter('frontend_visibility', array('eq' => array(1)))
                    ->addDefaultStoreFilter(Mage::app()->getStore()->getId());
            foreach ($segmentsCollection as $segment) {
                $segmentsToSubscribe[] = $segment->getCode();
            }
            try {
                Mage::getModel('advancednewsletter/subscriber')
                    ->setCustomer($customer)
                    ->subscribe($customer->getEmail(), $segmentsToSubscribe)
                ;
            } catch (Exception $ex) {
                
            }
        } else {
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($customer->getEmail());
            if ($subscriber->getId() && $subscriber->getStoreId() == Mage::app()->getStore()->getId()) {
                try {
                    $subscriber
                        ->setCustomerId($customer->getId())
                        ->setFirstName($customer->getFirstname())
                        ->setLastName($customer->getLastname())
                        ->setPhone(
                            $customer->getPrimaryBillingAddress() ?
                                $customer->getPrimaryBillingAddress()->getTelephone() :
                                ''
                        )
                        ->save()
                    ;
                } catch (Exception $ex) {

                }
            }
        }
    }

    /**
     * Change customers segments after changing them at customer edit > newsletter section
     * @param Mage_Customer_Model_Customer $customer 
     */
    protected function updateSegmentsFromAdmin($customer)
    {
        if (Mage::app()->getRequest()->getParam('an_segments_changed')) {
            $segments = array();
            if (Mage::app()->getRequest()->getParam('segments_codes')) {
                $segments = Mage::app()->getRequest()->getParam('segments_codes');
            }
            $subscriber = Mage::getModel('advancednewsletter/subscriber')
                ->loadByEmail($customer->getEmail())
            ;
            if ($subscriber->getId()) {
                if (empty($segments)) {
                    $subscriber->unsubscribeFromAll();
                } else {
                    $subscriber->forceWrite(
                        array(
                             'segments_codes' => $segments,
                             'status' => AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED
                        )
                    );
                }
            } else {
                if (!empty($segments)) {
                    $subscriber->setCustomer($customer)->subscribe($customer->getEmail(), $segments);
                }
            }
        }
    }

    /**
     * Save customer observer
     * @param mixed $observer
     */
    public function saveCustomer($observer)
    {
        //TODO: Optimize observing may be. subscribeAfterRegistration function espessially
        $customer = $observer->getEvent()->getCustomer();
        if (($customer instanceof Mage_Customer_Model_Customer)) {
            $this->updateNames($customer);
            $this->subscribeAfterRegistration($customer);
            $this->updateSegmentsFromAdmin($customer);
        }
    }

    /**
     * Observing order status changes to apply automanagement rules
     * @param mixed $observer 
     */
    public function orderStatusChanged($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderStatus = $order->getStatus();
        if ($orderStatus) {
            /* checkout onepage subscription */
            if ($this->_getAwAnOnepageData('aw_an_onepage')) {
                $this->_subscribeOnOnepageCheckout($observer);
                if (null !== Mage::getSingleton('customer/session')->getAwAnOnepageData()) {
                    Mage::getSingleton('customer/session')->setAwAnOnepageData(null);
                }
            }

            $customerEmail = $order->getCustomerEmail();
            $storeId = $order->getStoreId();

            $sku = array();
            $categoryIds = array();
            $product = Mage::getModel('catalog/product');

            foreach ($order->getAllItems() as $item) {
                $product->load($item->getProductId());

                $productCategoriesWithParents = $product->getCategoryIds();
                foreach ($productCategoriesWithParents as $productCategory) {
                    $productCategoriesWithParents = array_merge(
                        $productCategoriesWithParents,
                        Mage::getModel('catalog/category')->load($productCategory)->getParentIds()
                    );
                }
                $categoryIds[] = array_unique($productCategoriesWithParents);
                $sku[] = $item->getSku();
            }

            $allCategories = array();
            foreach ($categoryIds as $categoryId) {
                foreach ($categoryId as $item) {
                    $flag = false;
                    foreach ($allCategories as $category) {
                        if ($category == $item) {
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        $allCategories[] = $item;
                    }
                }
            }


            $toValidate = new Varien_Object();
            $toValidate->setData('categories', $allCategories);
            $toValidate->setData('sku', $sku);
            $toValidate->setData('store', $storeId);
            $toValidate->setData('order_status', $orderStatus);
            $toValidate->setData('customer_email', $customerEmail);
            $toValidate->setData('order', $order);

            Mage::getModel('advancednewsletter/automanagement')->checkRule($toValidate);
        }
    }

    /*
     *   Subscribe on OnepageCheckout (called from $this->orderStatusChanged())
     * 
     */

    protected function _subscribeOnOnepageCheckout($observer)
    {
        try {
            $segments = $this->_getAwAnOnepageData('segments_select'); // get subscribe form data
            $email = Mage::helper('advancednewsletter')->getCheckoutStoredEmail();  // get email to subscribe

            $subscriber = Mage::getSingleton('advancednewsletter/subscriber')->loadByEmail($email);

            $subscriberId = $subscriber->getData('id');

            if ($segments == '') {
                if ($subscriberId) {
                    // unsubscribeFromAll On Checkout
                    $checkoutSegments = Mage::helper('advancednewsletter')->getSegmentsCodesOnCheckout();

                    switch ($subscriber->getStatus()) {
                        case AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE:
                            $subscriber->removeSegments($checkoutSegments);
                            if (!$subscriber->getSegmentsCodes()) {
                                $subscriber->setStatus(AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
                            }
                            $subscriber->save();
                            break;

                        case AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                            $subscriber->unsubscribe($checkoutSegments);
                            break;

                        case AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                            // nothing to do
                            break;

                        default:
                            break;
                    }
                } else {
                    // nothing to do
                }
            } else {
                if (!is_array($segments)) {
                    $segments = explode(',', $segments);
                }

                if ($subscriberId) {
                    // unsubscribe from all segments visible on checkout
                    // subscribe to selected segments

                    $checkoutSegments = Mage::helper('advancednewsletter')->getSegmentsCodesOnCheckout();

                    $unsubsSegments = array_diff($checkoutSegments, $segments);
                    if ($unsubsSegments) {
                        $subscriber->removeSegments($unsubsSegments);
                    }
                    $subscriber->addSegments($segments);
                    $subscriber->setStatus(AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED);
                    $subscriber->save();
                } else {
                    // new subscriber
                    $orderData = $observer->getOrder()->getData();
                    $params = array(
                        'store_id' => Mage::app()->getStore()->getId(),
                        'first_name' => isset($orderData['customer_firstname']) ? $orderData['customer_firstname'] : '',
                        'last_name' => isset($orderData['customer_lastname']) ? $orderData['customer_lastname'] : '',
                    );

                    // subscribe to selected
                    $subscriber->subscribe($email, $segments, $params);
                }
            }
        } catch (Exception $exc) {
            Mage::helper('awcore/logger')->log($this, 'OnepageCheckout Segments update failed - ' . $exc->getMessage());
        }
    }

    public function sagepaysuitePaymentOnepageSaveOrder()
    {
        if (Mage::app()->getRequest()->getParam('aw_an_onepage')) {
            Mage::getSingleton('customer/session')->setAwAnOnepageData(new Varien_Object(
                    array(
                         'aw_an_onepage'   => 1,
                         'segments_select' => Mage::app()->getRequest()->getParam('segments_select')
                    )
                )
            );
        }
        return $this;
    }

    protected function _getAwAnOnepageData($name)
    {
        $value = Mage::app()->getRequest()->getParam($name, null);
        $anSessionData = Mage::getSingleton('customer/session')->getAwAnOnepageData();
        if (null === $value &&  null !== $anSessionData) {
            $value = $anSessionData->getData($name);
        }
        return $value;
    }

    public function subscriberSubscribe($observer)
    {
        if (
            !Mage::helper('advancednewsletter')->isChimpEnabled() ||
            !Mage::helper('advancednewsletter')->isChimpAutoSyncEnabled() ||
            Mage::registry('an_disable_autosync')
        ) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($subscriber->getStoreId())
                ->subscribe($subscriber)
            ;
        } catch (Exception $ex) {

        }
    }

    public function subscriberUnsubscribe($observer)
    {
        if (
            !Mage::helper('advancednewsletter')->isChimpEnabled() ||
            !Mage::helper('advancednewsletter')->isChimpAutoSyncEnabled() ||
            Mage::registry('an_disable_autosync')
        ) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($subscriber->getStoreId())
                ->unsubscribe($subscriber)
            ;
        } catch (Exception $ex) {

        }
    }

    public function subscriberDelete($observer)
    {
        if (
            !Mage::helper('advancednewsletter')->isChimpEnabled() ||
            !Mage::helper('advancednewsletter')->isChimpAutoSyncEnabled() ||
            Mage::registry('an_disable_autosync')
        ) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($subscriber->getStoreId())
                ->delete($subscriber)
            ;
        } catch (Exception $ex) {

        }
    }

    public function subscriberForceWrite($observer)
    {
        if (
            !Mage::helper('advancednewsletter')->isChimpEnabled() ||
            !Mage::helper('advancednewsletter')->isChimpAutoSyncEnabled() ||
            Mage::registry('an_disable_autosync')
        ) {
            return $this;
        }
        try {
            $subscriber = $observer->getSubscriber();
            AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($subscriber->getStoreId())
                ->forceWrite($subscriber)
            ;
        } catch (Exception $ex) {

        }
    }

    public function removeSegment($observer)
    {
        if (
            !Mage::helper('advancednewsletter')->isChimpEnabled() ||
            !Mage::helper('advancednewsletter')->isChimpAutoSyncEnabled() ||
            Mage::registry('an_disable_autosync')
        ) {
            return $this;
        }
        if ($observer->getSegmentCode()) {
            try {
                foreach (Mage::app()->getStores() as $store) {
                    AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($store->getId())
                        ->removeSegment($observer->getSegmentCode())
                    ;
                }
            } catch (Exception $ex) {

            }
        }
    }

    public function controllerActionPredispatch()
    {
        if((bool)Mage::helper('core')->isModuleOutputEnabled('AW_Advancednewsletter') == true) {
            $config = Mage::getConfig();
            $node = $config->getNode('global/blocks/adminhtml/rewrite');
            $dnode = $config->getNode('global/blocks/adminhtml/drewrite/customer_edit_tab_newsletter');
            $node->appendChild($dnode);
            $dnode = $config->getNode('global/blocks/adminhtml/drewrite/newsletter_template_preview');
            $node->appendChild($dnode);
            $dnode = $config->getNode('global/blocks/adminhtml/drewrite/page_menu');
            $node->appendChild($dnode);
        }
    }
}
