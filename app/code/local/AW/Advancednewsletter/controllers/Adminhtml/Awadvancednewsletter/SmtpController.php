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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_SmtpController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Smtp'));
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
        $model = Mage::getModel('advancednewsletter/smtp')->load($this->getRequest()->getParam('id'));
        if ($model->getId()) {
            Mage::register('an_current_smtp', $model);
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('Edit Smtp');
        } else {
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('New Smtp');
        }

        $this
            ->displayTitle()
            ->loadLayout()
        ;
        $this->_setActiveMenu('advancednewsletter');
        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);
        $this->_addContent($this->getLayout()->createBlock('advancednewsletter/adminhtml_smtp_edit'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            $request = $this->getRequest();
            if ($data = $request->getPost()) {
                $smtp = Mage::getModel('advancednewsletter/smtp');
                if ($request->getParam('id')) {
                    $smtp->load($request->getParam('id'));
                }
                $smtp->addData($data)->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Successfully saved')
                );
                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect('*/*/edit', array('id' => $smtp->getId()));
                }
            }
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__('Unable to find item to save')
            );
            return $this->_redirect('*/*/');
        }
        return $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id')) {
            try {
                Mage::getModel('advancednewsletter/smtp')->load($this->getRequest()->getParam('id'))->delete();
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

    public function massDeleteAction()
    {
        $smtpIds = $this->getRequest()->getParam('smtp_id');
        if (!is_array($smtpIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($smtpIds as $smtpId) {
                    Mage::getModel('advancednewsletter/smtp')->load($smtpId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($smtpIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/smtpconfiguration');
    }

    /*
     *   testconnectionAction()
     *   
     */

    public function testconnectionAction()
    {
        $result = AW_Advancednewsletter_Model_Form_Element_Testconnection::STATUS_FAIL;
        $data = $this->getRequest()->getParams();
        if (
                (!isset($data['server_name'])) || ($data['server_name'] === '')
                || (!isset($data['port'])) || ((int) $data['port'] == 0)
                || (!isset($data['user_name'])) || ($data['user_name'] === '')
                || (!isset($data['password'])) || ($data['password'] === '')
                || (!isset($data['usessl'])) /*  use TLS, 0 or 1  */
        ) {
            $result = array('result' => $result,);
            $this->getResponse()->setBody(Zend_Json::encode($result));
            return $this;
        }

        $configMail = array(
            'auth' => 'login',
            'username' => $data['user_name'],
            'password' => $data['password'],
            'port' => (int) $data['port'],
        );

        if ($data['usessl'] == 1) {
            $configMail['ssl'] = 'tls';
        }
        if ($data['usessl'] == 2) {
            $configMail['ssl'] = 'ssl';
        }

        try {
            $connection = new Zend_Mail_Protocol_Smtp_Auth_Login($data['server_name'], $data['port'], $configMail);
            $connection->connect();
            $connection->helo();
            $result = AW_Advancednewsletter_Model_Form_Element_Testconnection::STATUS_SUCCESS;
        } catch (Exception $exc) {
            $result = AW_Advancednewsletter_Model_Form_Element_Testconnection::STATUS_FAIL;
        }
        $result = array('result' => $result);
        $this->getResponse()->setBody(Zend_Json::encode($result));
        return $this;
    }

}