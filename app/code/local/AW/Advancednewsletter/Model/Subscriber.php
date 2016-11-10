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


class AW_Advancednewsletter_Model_Subscriber extends Mage_Core_Model_Abstract
{
    const STATUS_SUBSCRIBED = 1;
    const STATUS_UNSUBSCRIBED = 2;
    const STATUS_NOTACTIVE = 3;

    const NEED_TO_CONFIRM = 'newsletter/subscription/confirm';

    protected $_customer;
    protected $_encryptor;

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/subscriber');
        $this->_encryptor = Mage::getModel('core/encryption');
    }

    protected function _beforeSave()
    {
        if (Mage::helper('advancednewsletter')->extensionEnabled('AW_Points')) {
            if ($this->getOldData('status') == self::STATUS_NOTACTIVE || !$this->getId()) {
                $this->setData('add_points', true);
            }
        }
    }

    protected function _afterSave()
    {
        if (Mage::helper('advancednewsletter')->extensionEnabled('AW_Points')) {
            if ($this->getData('add_points')
                && $this->getStatus() == self::STATUS_SUBSCRIBED
                && $this->getCustomer()->getId()) {
                $pointsForSubscription = Mage::helper('points/config')
                    ->getPointsForNewsletterSingup()
                ;
                Mage::getModel('points/api')->addTransaction(
                    $pointsForSubscription, 'an_customer_subscription', $this->getCustomer(), $this
                );
            }
        }
    }

    /**
     * Loads subscriber by his email
     * @param string $email
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function loadByEmail($email)
    {
        return $this->load($email, 'email');
    }

    public function loadByCustomer($customer)
    {
        return $this->load($customer->getId(), 'customer_id');
    }

    /**
     * Subscribing process
     *
     * @param string $email
     * @param mixed $segments
     * @param array $params
     *
     * @return AW_Advancednewsletter_Model_Subscriber
     * @throws Exception
     */
    public function subscribe($email, $segments, $params = array())
    {
        $helper = Mage::helper('advancednewsletter');
        /* Check if customer subscribes */
        if ($this->getCustomer()->getId() && !isset($params['skip_customer_check'])) {
            $email = $this->getCustomer()->getEmail();
            $params['first_name'] = $this->getCustomer()->getFirstname();
            $params['last_name'] = $this->getCustomer()->getLastname();
            $params['customer_id'] = $this->getCustomer()->getId();
            if ($this->getCustomer()->getPrimaryBillingAddress()) {
                $params['phone'] = $this->getCustomer()->getPrimaryBillingAddress()->getTelephone();
            }
            if (!$this->getStoreId())
                $params['store_id'] = $this->getCustomer()->getStoreId();
        } elseif (!isset($params['skip_guest_check'])) {
            if (!preg_match('/^1.3/', Mage::getVersion()) && !preg_match('/^1.4.0/', Mage::getVersion())) {
                $allowGuests = Mage::getStoreConfig(
                    Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG
                );
                if (empty($allowGuests)) {
                    throw new Exception($helper->__('Please, register to subscribe'));
                }
            }

            if (empty($email)) {
                throw new Exception($helper->__('Email is empty'));
            }
        }

        $validator = new Zend_Validate_EmailAddress();
        if (!$validator->isValid($email)) {
            throw new Exception($helper->__('Invalid email'));
        }


        $this->loadByEmail($email);

        /* If subscriber doesn't exist or is not activated, set new flag to him */
        if (!$this->getId() || $this->getStatus() == self::STATUS_NOTACTIVE) {
            $this->setIsNew(true);
        }
        /* If unsubscriber subscribes again, no need to run activation process, send successfully subscribed only */
        if (
                ($this->getStatus() == self::STATUS_UNSUBSCRIBED)
                && ($helper->isSubNotificationEnabled() )
        ) {
            Mage::getModel('advancednewsletter/template')->sendFirstSubscribeMail($this);
        }
        /* Saving subscriber data */

        $this->sanitizeSegments($segments);

        $this
            ->setEmail($email)
            ->addSegments($segments)
            ->addData($params)
        ;

        $newSubscriber = false;
        if ($this->getIsNew()) {
            $newSubscriber = true;
            $this->processNewSubscriber();
        } else {
            $this->setStatus(self::STATUS_SUBSCRIBED);
        }

        if (!$this->getStoreId()) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }

        $this->save();

        if ($newSubscriber
            && $this->getStatus() == self::STATUS_SUBSCRIBED
            && $helper->isSubNotificationEnabled()
        ) {
            Mage::getModel('advancednewsletter/template')->sendFirstSubscribeMail($this);
        }

        Mage::dispatchEvent('an_subscriber_subscribe', array('subscriber' => $this));
        return $this;
    }

    /**
     * Unsubscribing from segments process
     * @param mixed $segments
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function unsubscribe($segments)
    {
        $this->removeSegments($segments);
        if (!count($this->getSegmentsCodes())) {
            $this->unsubscribeFromAll();
        } else {
            $this->save();
            Mage::dispatchEvent('an_subscriber_unsubscribe', array('subscriber' => $this));
        }
        return $this;
    }

    /**
     * Unsubscribing from all segments
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function unsubscribeFromAll()
    {
        $this
            ->setSegmentsCodes(array())
            ->setStatus(self::STATUS_UNSUBSCRIBED)
            ->save()
        ;
        if (Mage::helper('advancednewsletter')->isUnsubNotificationEnabled()) {
            Mage::getModel('advancednewsletter/template')->sendUnsubscribeMail($this);
        }
        Mage::dispatchEvent('an_subscriber_unsubscribe', array('subscriber' => $this));
        return $this;
    }

    /**
     * Deleting subscriber
     */
    public function delete()
    {
        Mage::dispatchEvent('an_subscriber_delete', array('subscriber' => $this));
        parent::delete();
    }

    /**
     * Direct writing data to subscriber table
     * @param array $data
     */
    public function forceWrite($data)
    {
        $this->addData($data)->save();
        Mage::dispatchEvent('an_subscriber_force_write', array('subscriber' => $this));
    }

    /**
     * Activation by confirm code
     * @param string $confirmCode
     * @throws Exception
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function activate($confirmCode)
    {
        if ($this->getConfirmCode() != $confirmCode || $this->getStatus() != self::STATUS_NOTACTIVE) {
            throw new Exception(Mage::helper('advancednewsletter')->__('Activation code is incorrect'));
        }
        /* If subscriber became subscribed from unactive, change status, set new flag and save */
        $this->setStatus(self::STATUS_SUBSCRIBED)->setIsNew(true)->save();
        Mage::getModel('advancednewsletter/template')->sendFirstSubscribeMail($this);
        Mage::dispatchEvent('an_subscriber_subscribe', array('subscriber' => $this));
        return $this;
    }

    /**
     * Adding segments to current subscriber segments
     * @param mixed $segments
     * @throws Exception
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function addSegments($segments)
    {
        if (empty($segments)) {
            throw new Exception(Mage::helper('advancednewsletter')->__('Segments are not set'));
        }
        if (!is_array($segments)) {
            $segments = explode(',', $segments);
        }
        $segmentsToAdd = array_unique(array_merge($segments, $this->getSegmentsCodes()));
        foreach ($segmentsToAdd as $key => $segment) {
            if (empty($segment)) {
                unset($segmentsToAdd[$key]);
            }
        }
        return $this->setSegmentsCodes($segmentsToAdd);
    }

    public function sanitizeSegments(&$segments)
    {
        if (!is_array($segments)) {
            $segments = htmlspecialchars($segments);
            return;
        }
        foreach ($segments as &$segment) {
             $segment = htmlspecialchars($segment);
        }
    }

    /**
     * Removing segments from current subscriber segments
     * @param mixed $segments
     * @throws Exception
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function removeSegments($segments)
    {
        if (empty($segments)) {
            throw new Exception(Mage::helper('advancednewsletter')->__('Segments are not set'));
        }
        if (!is_array($segments)) {
            $segments = explode(',', $segments);
        }
        $customerSegments = $this->getSegmentsCodes();
        if (empty($customerSegments)) {
            return $this;
        }

        foreach ($customerSegments as $key => $segment) {
            if (in_array($segment, $segments)) {
                unset($customerSegments[$key]);
            }
        }
        return $this->setSegmentsCodes($customerSegments);
    }

    /**
     * Processing new subscriber. Mails sending and statuses changing according to admin need to confirm property
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    protected function processNewSubscriber()
    {
        $needToConfirm = (int) Mage::getStoreConfig(self::NEED_TO_CONFIRM);
        $anTemplateModel = Mage::getModel('advancednewsletter/template');
        /* No need to activate customer */
        if ($needToConfirm && !$this->getCustomer()->getId()) {
            $this->setStatus(self::STATUS_NOTACTIVE);
            $this->setConfirmCode(md5(rand(1, 1000000)));
            $anTemplateModel->sendActivationMail($this);
        } else {
            $this->setStatus(self::STATUS_SUBSCRIBED);
        }
        return $this;
    }

    /**
     * Get customer from session
     * @return Mage_Customer_Model_Customer
     */
    protected function getCustomer()
    {
        if (!$this->_customer) {
            $this->_customer = Mage::getModel('customer/session')->getCustomer();
        }
        return $this->_customer;
    }

    /**
     * Set customer as subscriber
     * @param Mage_Customer_Model_Customer $customer
     * @return AW_Advancednewsletter_Model_Subscriber
     */
    public function setCustomer($customer)
    {
        $this->_customer = $customer;
        return $this;
    }

    /**
     * Returns subscribers segments
     * @param bool $withDisabledOnFrontend
     * @return array
     */
    public function getSegments($withDisabledOnFrontend = true)
    {
        $segments = array();
        foreach ($this->getSegmentsCodes() as $segmentCode) {
            $segment = Mage::getModel('advancednewsletter/segment')->load($segmentCode, 'code');
            if ((!$withDisabledOnFrontend && $segment->getFrontendVisibility()) || $withDisabledOnFrontend) {
                $segments[] = $segment;
            }
        }
        return $segments;
    }

    /**
     * Mark receiving subscriber of queue newsletter
     *
     * @param  AW_Advancednewsletter_Model_Queue $queue
     * @return boolean
     */
    public function received(AW_Advancednewsletter_Model_Queue $queue)
    {
        $this->getResource()->received($this, $queue);
        return $this;
    }

    /**
     * Returns unsubscription link for subscriber
     * @deprecated since 2.0.2 use getUnsubscriptionLink()
     * @return string
     */
    public function getUnsubscribeAllLink()
    {
        return $this->getUnsubscriptionLink();
    }

    /**
     * Returns unsubscription link for subscriber
     * @return string
     */
    public function getUnsubscriptionLink()
    {
        $key = $this->getAnMailEncryption($this->getId());
        $key = Mage::helper('core')->urlEncode($key);
        return Mage::app()->getStore($this->getStoreId())->getBaseUrl()
            . "advancednewsletter/index/unsubscribeAll/email/{$this->getEmail()}/key/{$key}"
        ;
    }

    public function getSalutationText()
    {
        if ($this->getSalutation() == 1) {
            $salutation = Mage::getStoreConfig(AW_Advancednewsletter_Block_Subscribe::SALUTATION_SECOND);
        } else {
            $salutation = Mage::getStoreConfig(AW_Advancednewsletter_Block_Subscribe::SALUTATION_FIRST);
        }
        return $salutation;
    }

    public function needActivation()
    {
        return $this->getData('is_new')
            && Mage::getStoreConfig(self::NEED_TO_CONFIRM)
            && !$this->getCustomer()->getId()
            ;
    }

    public function getAnMailEncryption($string)
    {
        return $this->_encryptor->encrypt($string);
    }

}
