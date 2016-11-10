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


class AW_Advancednewsletter_IndexController extends Mage_Core_Controller_Front_Action
{
    const SEGMENTS_STYLE = 'advancednewsletter/formconfiguration/segmentsstyle';
    const DEFAULT_SUBSCRIPTION = 'advancednewsletter/formconfiguration/defaultsubscription';

    public function subscribeAction()
    {
        $request = $this->getRequest();
        $segmentsToSubscribe = array();


        /* If customer subscribes as a guest we should first check if there is already customer with such email */
        $customer = Mage::getModel('customer/customer')
            ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
            ->loadByEmail($request->getParam('email'));
        if ($customer->getId()) {
            Mage::getSingleton('core/session')->addError(
                Mage::helper('advancednewsletter')->__(
                    'You must be logged in with this email to subscribe.'
                )
            );
            $this->getResponse()->setRedirect($this->_getRefererUrl());
            return;
        }
        /* */

        if (
                Mage::getStoreConfig(self::SEGMENTS_STYLE) ==
                AW_Advancednewsletter_Model_Source_Segmentsstyle::STYLE_NONE
        ) {
            /* only visible in store */
            $segmentsCollection = Mage::getModel('advancednewsletter/segment')
                    ->getCollection()
                    ->addDefaultStoreFilter(Mage::app()->getStore()->getId());

            if (
                    Mage::getStoreConfig(self::DEFAULT_SUBSCRIPTION)
                    == AW_Advancednewsletter_Model_Source_Defaultsubscription::CATEGORY_DEFAULT
            ) {

                $categoryId = $request->getParam('an_category_id');
                if (!$categoryId) {
                    $layer = Mage::getSingleton('catalog/layer');
                    $_category = $layer->getCurrentCategory();
                    $categoryId = $_category->getId();
                }
                $segmentsCollection
                        ->addDefaultCategoryFilter($categoryId);
            }

            foreach ($segmentsCollection as $segment) {
                $segmentsToSubscribe[] = $segment->getCode();
            }
        } else {
            $segmentsToSubscribe = $request->getParam('segments_select', array());
        }

        try {
            $subscriber = Mage::getModel('advancednewsletter/subscriber')
                ->subscribe($request->getParam('email'), $segmentsToSubscribe, $request->getParams())
            ;
            if ($subscriber->needActivation()) {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('advancednewsletter')->__('The email with activation code is send to your address')
                );
            } else {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('advancednewsletter')->__('You have been successfully subscribed')
                );
            }
        } catch (Exception $ex) {
            Mage::getSingleton('core/session')->addError($ex->getMessage());
        }
        $this->getResponse()->setRedirect($this->_getRefererUrl());
    }

    public function unsubscribeAction()
    {
        $subscriber = $this->_getFromRequest();
        if ($subscriber) {
            try {
                $subscriber->unsubscribe($this->getRequest()->getParam('segments_codes'));
                $this->_singleton()->addSuccess($this->_helper()->__('You have been successfully unsubscribed'));
            } catch (Exception $e) {
                $this->_singleton()->addError($e->getMessage());
            }
        } else {
            $this->_singleton()->addError($this->_helper()->__('Unsubscription error'));
        }

        return $this->getResponse()->setRedirect($this->_getRefererUrl());
    }

    public function unsubscribeAllAction()
    {
        $subscriber = $this->_getFromRequest();
        if ($subscriber) {
            try {
                if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                    $subscriber->unsubscribeFromAll();
                }
                $this->_singleton()->addSuccess($this->_helper()->__('You have been successfully unsubscribed'));
            } catch (Exception $e) {
                $this->_singleton()->addError($e->getMessage());
            }
        } else {
            $this->_singleton()->addError($this->_helper()->__('Unsubscription error'));
        }

        $this->getResponse()->setRedirect($this->_getRefererUrl());
    }

    protected function _getFromRequest()
    {
        $request = $this->getRequest();

        if (!$key = (int) $this->_helper()->decrypt($request->getParam('key'))) {
            return false;
        }

        $subscriber = $this->_model()->loadByEmail($request->getParam('email'));

        if ($subscriber->getId() && (int) $subscriber->getId() === (int) $key) {
            return $subscriber;
        }

        return false;
    }
    
    protected function _singleton($type = 'core/session')
    {
        return Mage::getSingleton($type);
    }

    protected function _model($type = 'advancednewsletter/subscriber')
    {
        return Mage::getModel($type);
    }

    protected function _helper($type = 'advancednewsletter')
    {
        return Mage::helper($type);        
    }

    public function activateAction()
    {
        try {
            Mage::getModel('advancednewsletter/subscriber')
                    ->loadByEmail($this->getRequest()->getParam('email'))
                    ->activate($this->getRequest()->getParam('confirm_code'));
            Mage::getSingleton('core/session')->addSuccess(
                Mage::helper('advancednewsletter')->__('Your subscription is activated now')
            );
        } catch (Exception $ex) {
            Mage::getSingleton('core/session')->addError($ex->getMessage());
        }
        $this->_redirectUrl(Mage::getBaseUrl());
    }

    public function subscribeAjaxAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function updateStatusAction()
    {
        try {
            $segments = $this->getRequest()->getParam('segments_codes', array());
            $subscriber = Mage::getModel('advancednewsletter/subscriber')
                ->loadByEmail($this->getRequest()->getParam('email'))
            ;
            if ($subscriber->getId()
                && $subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
                $currentStoreId = Mage::app()->getStore()->getId();
                $unVisibleSegmentsCodes = array();
                $currentSubscriberSegments = $subscriber->getSegments();
                foreach ($currentSubscriberSegments as $currentSubscriberSegment) {
                    $isNotVisibleOnStore = !$currentSubscriberSegment->getFrontendVisibility()
                        || !in_array($currentStoreId, $currentSubscriberSegment->getDisplayInStore());
                    if ($isNotVisibleOnStore) {
                        $unVisibleSegmentsCodes[] = $currentSubscriberSegment->getCode();
                    }
                }
                $segments = array_merge($unVisibleSegmentsCodes, $segments);
                if (empty($segments)) {
                    $subscriber->unsubscribeFromAll();
                } else {
                    $subscriber->forceWrite(
                        array(
                             'segments_codes' => $segments,
                             'customer_id' => Mage::getModel('customer/session')->getCustomer()->getId()
                        )
                    );
                }

            } else {
                $customer = Mage::getModel('customer/session')->getCustomer();
                $subscriber->setCustomer($customer)->subscribe($customer->getEmail(), $segments);
            }
            Mage::getSingleton('core/session')->addSuccess(
                Mage::helper('advancednewsletter')->__('Your subscriptions have been successfully updated')
            );
        } catch (Exception $ex) {
            Mage::getSingleton('core/session')->addError($ex->getMessage());
        }
        $this->getResponse()->setRedirect(Mage::getUrl('advancednewsletter/manage/'));
    }

    public function viewWebVersionAction()
    {
        $key = $this->getRequest()->getParam('key', null);
        if (null !== $key) {
            $key = Mage::helper('advancednewsletter')->decrypt($key);
            list($email, $storedEmailId) = @explode(',', $key);
            if (!empty($email) && !empty($storedEmailId)) {
                $storedEmail = Mage::getResourceModel('advancednewsletter/storedEmails')->getStoredEmail($email, $storedEmailId);
                if (null !== $storedEmail->getId()) {
                    return $this->getResponse()->setBody($storedEmail->getContent());
                }
            }
        }
        $this->_forward('noRoute');
    }
}