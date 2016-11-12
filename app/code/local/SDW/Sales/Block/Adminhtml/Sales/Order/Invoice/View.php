<?php

class SDW_Sales_Block_Adminhtml_Sales_Order_Invoice_View extends Mage_Adminhtml_Block_Sales_Order_Invoice_View
{
	public function getButtonsHtml()
	{
		$out = parent::getButtonsHtml();

		$current_status = $this->getInvoice()->getCurrentStatus();
		$selected       = 'selected="selected"';

		$out .= vsprintf(
			'
			<select onchange="location.href = \'%sinvoice_id/%d/status/\'+this.value;">
				<option value="%s" %s>%s</option>
				<option value="%s" %s>%s</option>
				<option value="%s" %s>%s</option>
			</select>
			',
			array(
				$this->getUrl('*/*/changeStatus'),
				Mage::app()->getRequest()->getParam('invoice_id'),
				SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TO_BE_EXPORTED,
				($current_status === SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TO_BE_EXPORTED ? $selected : ''),
				$this->__('To be exported'),
				SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_NOT_TO_BE_EXPORTED,
				($current_status === SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_NOT_TO_BE_EXPORTED ? $selected : ''),
				$this->__('Not to be exported'),
				SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_EXPORTED,
				($current_status === SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_EXPORTED ? $selected : ''),
				$this->__('Exported'),
			)
		);

		return $out;
	}
}
