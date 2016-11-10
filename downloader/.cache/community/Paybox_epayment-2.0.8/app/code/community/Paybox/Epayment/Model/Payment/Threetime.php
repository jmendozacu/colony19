<?php
/**
 * Paybox Epayment module for Magento
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * available at : http://opensource.org/licenses/osl-3.0.php
 *
 * @package    Paybox_Epayment
 * @copyright  Copyright (c) 2013-2014 Paybox
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Paybox_Epayment_Model_Payment_Threetime extends Paybox_Epayment_Model_Payment_Abstract {

    protected $_code = 'pbxep_threetime';
    protected $_hasCctypes = true;
    protected $_allowRefund = true;
    protected $_3dsAllowed = true;

    public function getConfigPaidPartiallyStatus() {
        return $this->getConfigData('status/partiallypaid');
    }


    public function checkIpnParams(Mage_Sales_Model_Order $order, array $params) {
        if (!isset($params['amount'])) {
            $message = $this->__('Missing amount parameter');
            $this->logFatal(sprintf('Order %s: (IPN) %s', $order->getIncrementId(), $message));
            Mage::throwException($message);
        }
        if (!isset($params['transaction'])) {
            $message = $this->__('Missing transaction parameter');
            $this->logFatal(sprintf('Order %s: (IPN) %s', $order->getIncrementId(), $message));
            Mage::throwException($message);
        }
    }

    public function onIPNSuccess(Mage_Sales_Model_Order $order, array $data) {

        $payment = $order->getPayment();
        $this->logDebug(sprintf('Order %s: %s-time IPN', $order->getIncrementId(),$this->getNbtimes()));

        // Message

        // Create transaction
        $type = Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE;
        $txn = $this->_addPayboxTransaction($order, $type, $data, true, array(
            self::CALL_NUMBER => $data['call'],
            self::TRANSACTION_NUMBER => $data['transaction'],
        ));
        if (is_null($payment->getPbxepFirstPayment())) {
            $this->logDebug(sprintf('Order %s: First payment', $order->getIncrementId()));

            // Message
            $message = 'First Payment was authorized and captured by Paybox.';

            // Status
            $status = $this->getConfigPaidpartiallyStatus();
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
            $allowedStates = array(
                Mage_Sales_Model_Order::STATE_NEW,
                Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage_Sales_Model_Order::STATE_PROCESSING,
            );
            $current = $order->getState();
            $message = $this->__($message);

            // Additional informations
            $payment->setPbxepFirstPayment(serialize($data));
            $payment->setPbxepAuthorization(serialize($data));
			
			$order->sendNewOrderEmail();

				
            if (in_array($current, $allowedStates)) {
                $order->setState($state, $status, $message);
				$this->logDebug(sprintf('Order %s: changing state from: %s to %s', $order->getIncrementId(), $current, $status));
            } else {
                $order->addStatusHistoryComment($message);
            }
            $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));

        } else if (is_null($payment->getPbxepSecondPayment())) {
            // Message
            $message = 'Second payment was captured by Paybox.';
            $order->addStatusHistoryComment($message);
            // Additional informations
			$payment->setPbxepSecondPayment(serialize($data));				
            $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));
			if($payment->getNbtimes() === 2){
				$this->_createInvoice($order, $txn);
				// Client notification if needed
			}
        } else if (is_null($payment->getPbxepThirdPayment()) && $this->getNbtimes()>=3) {
            // Message
            $message = 'Third payment was captured by Paybox.';
            $order->addStatusHistoryComment($message);

            // Additional informations
			$payment->setPbxepThirdPayment(serialize($data));
            $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));
			if($this->getNbtimes() === 3){
				$this->_createInvoice($order, $txn);
				// Client notification if needed
			}
        } else if (is_null($payment->getPbxepFourthPayment()) && $this->getNbtimes()==4) {
            // Message
            $message = 'Fourth payment was captured by Paybox.';
            $order->addStatusHistoryComment($message);

			// Client notification if needed
            $this->logDebug(sprintf('Order %s: %s', $order->getIncrementId(), $message));
            // Additional informations
			$this->_createInvoice($order, $txn);
        } else {
            $this->logDebug(sprintf('Order %s: Invalid %s-time payment status', $order->getIncrementId(),$this->getNbtimes()));
            Mage::throwException('Invalid '.$this->getNbtimes().'-time payment status');
        }
        $data['status'] = $message;

        // Associate data to payment
        $payment->setPbxepAction('three-time');

        $transactionSave = Mage::getModel('core/resource_transaction');
        $transactionSave->addObject($payment);
        if (isset($invoice)) {
            $transactionSave->addObject($invoice);
        }
        $order->save();
        $transactionSave->save();
    }
	public function getNbtimes(){
		return $this->getConfigData('nbtimes');
	}
	public function getNbDays(){
		return $this->getConfigData('nbdays');
	}

	
}