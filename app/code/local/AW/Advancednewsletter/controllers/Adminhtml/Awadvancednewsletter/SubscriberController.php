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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_SubscriberController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Subscribers'));
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

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $subscriber = Mage::getModel('advancednewsletter/subscriber')->load($this->getRequest()->getParam('id'));
        if ($subscriber->getId()) {
            Mage::register('an_current_subscriber', $subscriber);
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('Edit Subscriber');
        } else {
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('New Subscriber');
        }

        $this
            ->displayTitle()
            ->loadLayout()
        ;
        $this->_setActiveMenu('advancednewsletter');
        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);
        $this->_addContent($this->getLayout()->createBlock('advancednewsletter/adminhtml_subscriber_edit'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            $request = $this->getRequest();
            if ($data = $request->getPost()) {
                $subscriber = Mage::getModel('advancednewsletter/subscriber');
                if ($request->getParam('id'))
                    $subscriber->load($request->getParam('id'));
                else
                    $subscriber->setIsNew(true);
                if (!isset($data['segments_codes']))
                    $data['segments_codes'] = array();
                $subscriber->forceWrite($data);
                if ($subscriber->getStatus() == AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED)
                    $subscriber->unsubscribeFromAll();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Successfully saved')
                );
                if ($this->getRequest()->getParam('back'))
                    return $this->_redirect('*/*/edit', array('id' => $subscriber->getId()));
            }
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__('Unable to find item to save')
            );
            return $this->_redirect('*/*/');
        }
        return $this->_redirect('*/*');
    }

    public function massSubscribeAction()
    {
        try {
            $segmentsToSubscribe = array($this->getRequest()->getParam('segment'));
            if ($this->getSubscriberIds()) {
                foreach ($this->getSubscriberIds() as $subscriberId) {
                    $subscriber = Mage::getModel('advancednewsletter/subscriber')->load($subscriberId);
                    $subscriber->subscribe(
                        $subscriber->getEmail(), $segmentsToSubscribe, array('skip_guest_check' => true)
                    );
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('Successfully subscribed')
            );
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__('Unable to find item to save')
            );
        }
        $this->_redirect('*/*/index');
    }

    public function massUnsubscribeAction()
    {
        try {
            $segmentsToUnsubscribe = array($this->getRequest()->getParam('segment'));
            if ($this->getSubscriberIds()) {
                foreach ($this->getSubscriberIds() as $subscriberId) {
                    Mage::getModel('advancednewsletter/subscriber')
                        ->load($subscriberId)
                        ->unsubscribe($segmentsToUnsubscribe)
                    ;
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('Successfully unsubscribed')
            );
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__('Unable to find item to save')
            );
        }
        $this->_redirect('*/*/index');
    }

    public function massDeleteAction()
    {
        try {
            if ($this->getSubscriberIds()) {
                foreach ($this->getSubscriberIds() as $subscriberId) {
                    Mage::getModel('advancednewsletter/subscriber')->load($subscriberId)->delete();
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Successfully deleted'));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__('Unable to find item to save')
            );
        }
        $this->_redirect('*/*/index');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id')) {
            try {
                Mage::getModel('advancednewsletter/subscriber')->load($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Item was successfully deleted')
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                return $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        return $this->_redirect('*/*/');
    }

    protected function getSubscriberIds()
    {
        $subscribersIds = $this->getRequest()->getParam('subscriber');
        if (!is_array($subscribersIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('newsletter')->__('Please select subscriber(s)')
            );
            return false;
        }
        return $subscribersIds;
    }

    /**
     * Export subscriber grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName = 'subscribers.csv';
        if (version_compare(Mage::getVersion(), '1.3.3.0', '<=')) {
            $content = $this->getLayout()->createBlock('advancednewsletter/adminhtml_subscriber_grid')->getCsv();
        } else {
            $content = $this->getLayout()->createBlock('advancednewsletter/adminhtml_subscriber_grid')->getCsvFile();
        }
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export subscriber grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName = 'subscribers.xml';
        if (version_compare(Mage::getVersion(), '1.3.3.0', '<=')) {
            $content = $this->getLayout()->createBlock('advancednewsletter/adminhtml_subscriber_grid')->getXml();
        } else {
            $content = $this->getLayout()->createBlock('advancednewsletter/adminhtml_subscriber_grid')->getExcelFile();
        }
        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/subscribers');
    }

}