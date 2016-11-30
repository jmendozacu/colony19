<?php

class IWD_OrderManager_Adminhtml_Settlementreport_TransactionsController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('iwd_settlementreport')
            ->_title($this->__('IWD - Settlement Reports'));

        $this->_addBreadcrumb(
            Mage::helper('iwd_ordermanager')->__('Settlement Reports'),
            Mage::helper('iwd_ordermanager')->__('Settlement Reports')
        );

        return $this;
    }

    public function indexAction()
    {
        $connection = Mage::helper('iwd_ordermanager')->checkApiCredentials();

        if ($connection['error'] == 0) {
            $this->_showLastExecutionTime();
            $this->_initAction();
            $this->_addContent($this->getLayout()->createBlock('iwd_ordermanager/adminhtml_transactions'));
        } else {
            $this->_initAction();
            $error_block = $this->getLayout()->createBlock('iwd_ordermanager/adminhtml_transactions_error');
            $error_block->setData('message', $connection['message']);
            $this->_addContent($error_block);
        }

        $this->renderLayout();
    }

    public function sendreportAction()
    {
        try {
            $email = $this->getRequest()->getParam('email', null);
            if (empty($email)) {
                throw new Exception('Email is empty.');
            }

            //Mage::getModel("iwd_settlementreport/transactions")->refresh();
            Mage::getModel('iwd_ordermanager/notify_report')->sendEmail($email);

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('iwd_ordermanager')->__('The reports were successfully sent.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__('Error: ') . $e->getMessage());
        }

        $this->_redirect('*/*/');
        return;
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('iwd_ordermanager/adminhtml_transactions_grid')->toHtml()
        );
    }

    public function updateAction()
    {
        try {
            Mage::getModel("iwd_ordermanager/transactions")->refresh();

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('iwd_ordermanager')->__('Refreshed Successfully'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__('Error: ') . $e->getMessage());
        }

        $this->_redirect('*/*/');
        return;
    }

    public function exportCsvAction()
    {
        $file_name = 'transactions.csv';
        $content = $this->getLayout()->createBlock('iwd_ordermanager/adminhtml_transactions_grid')
            ->getCsvFile();

        $this->_prepareDownloadResponse($file_name, $content);
    }

    public function exportExcelAction()
    {
        $file_name = 'transactions.xml';
        $content = $this->getLayout()->createBlock('iwd_ordermanager/adminhtml_transactions_grid')
            ->getExcelFile();

        $this->_prepareDownloadResponse($file_name, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/report/iwd_settlementreport');
    }

    protected function _showLastExecutionTime()
    {
        $flag = Mage::getModel('reports/flag')->setReportFlagCode('iwd_settlementreport_transactions')->loadSelf();
        $updated_at = ($flag->hasData())
            ? Mage::app()->getLocale()->storeDate(
                0, new Zend_Date($flag->getLastUpdate(), Varien_Date::DATETIME_INTERNAL_FORMAT), true
            )
            : 'undefined';

        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('adminhtml')->__('Last updated: %s.', $updated_at));
        return $this;
    }
}
