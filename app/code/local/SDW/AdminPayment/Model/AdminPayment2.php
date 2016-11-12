<?php

class SDW_AdminPayment_Model_AdminPayment2 extends Mage_Payment_Model_Method_Abstract
{

    protected $_code  = 'adminpayment2';
    protected $_canUseInternal              = true;
    protected $_canUseCheckout = false;
    protected $_canUseForMultishipping  = false;

    public function authorize(Varien_Object $payment, $total) {
        if($this->getConfigData('capture_auto') && $payment->getOrder()->canInvoice()) {
            $invoice = $payment->getOrder()->prepareInvoice();
            $invoice->register();
            $payment->getOrder()->addRelatedObject($invoice);
        }
        if($this->getConfigData('shipment_auto') && $payment->getOrder()->canShip()) {
            $shipment = $payment->getOrder()->prepareShipment();
            $shipment->register();
            $payment->getOrder()->addRelatedObject($shipment);
        }
        return $this;
    }

}
