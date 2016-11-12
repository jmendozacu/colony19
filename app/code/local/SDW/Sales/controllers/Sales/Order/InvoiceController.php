<?php

require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml').'/Sales/Order/InvoiceController.php';

class SDW_Sales_Sales_Order_InvoiceController extends Mage_Adminhtml_Sales_Order_InvoiceController
{
	public function changeStatusAction()
	{
		$invoice_id = intval($this->getRequest()->getParam('invoice_id'));
		$invoice    = Mage::getModel('sales/order_invoice')->load($invoice_id);
		$new_status = $this->getRequest()->getParam('status');

		// Sanity check
		if (!$invoice || !$invoice->getId() || !$new_status) {
			$this->_getSession()->addError($this->__('You must specify a valid invoice ID and a valid status.'));
			$this->_redirect('*/*/view', array('invoice_id' => $invoice_id));
			return;
		}

		try {
			$invoice->updateCurrentStatus($new_status);
		} catch (Exception $e) {
			$this->_getSession()->addError(
				sprintf(
					$this->__('There has been an error while updating the database &ndash; %s'),
					$e->getMessage()
				)
			);
			$this->_redirect('*/*/view', array('invoice_id' => $invoice_id));
			return;
		}

		$this->_getSession()->addSuccess($this->__('The status was updated successfully.'));
		$this->_redirect('*/*/view', array('invoice_id' => $invoice_id));
	}
}
