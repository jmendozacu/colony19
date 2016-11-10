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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_CustomerController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14())
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Customers'));
        return $this;
    }

    protected function _initCustomer($idFieldName = 'id')
    {
        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customer = Mage::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        Mage::register('current_customer', $customer);
        return $this;
    }

    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        $this
                ->displayTitle()
                ->loadLayout()
                ->_setActiveMenu('advancednewsletter');

        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('advancednewsletter/adminhtml_customer_grid')->toHtml()
        );
    }

    public function massSubscribeAction()
    {
        $segment = $this->getRequest()->getParam('segment');
        $customersIds = $this->getRequest()->getParam('customer');
        if (!is_array($customersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select customer(s)')
            );
        } else {
            try {
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::$disableAutosync = true;
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    /* We will subscribe customer to default store view of a website */
                    $defaultStore = Mage::getModel('core/website')->load($customer->getWebsiteId())->getDefaultStore();
                    if ($defaultStore) {
                        $customer->setStoreId($defaultStore->getId());
                    }

                    Mage::getModel('advancednewsletter/subscriber')
                        ->setCustomer($customer)
                        ->subscribe($customer->getEmail(), $segment)
                    ;
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully updated', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massUnsubscribeAction()
    {
        $segment = $this->getRequest()->getParam('segment');
        $customersIds = $this->getRequest()->getParam('customer');
        if (!is_array($customersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('Please select customer(s)')
            );
        } else {
            try {
                AW_Advancednewsletter_Model_Sync_Mailchimpclient::$disableAutosync = true;
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
                    Mage::getModel('advancednewsletter/subscriber')
                        ->loadByEmail($customer->getEmail())
                        ->unsubscribe($segment)
                    ;
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully updated', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function newsletterAction()
    {
        $this->_initCustomer();
        $subscriber = Mage::getModel('advancednewsletter/subscriber')
                ->loadByEmail(Mage::registry('current_customer')->getEmail());

        Mage::register('subscriber', $subscriber);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('advancednewsletter/adminhtml_customer_edit_tab_newsletter_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/exportcustomers');
    }

}
