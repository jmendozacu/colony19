<?php

class IWD_OrderManager_Model_Payment_Payment extends Mage_Core_Model_Abstract
{
    const IS_REAUTHORIZATION_ENABLED = true;

    protected $params;
    protected $_realTransactionIdKey = 'real_transaction_id';

    protected function init($params)
    {
        if (!isset($params['order_id'])) {
            throw new Exception("Order id is not defined");
        }
        $this->params = $params;
    }

    public function updateOrderPayment($params)
    {
        $this->init($params);

        if (isset($params['confirm_edit']) && !empty($params['confirm_edit'])) {
            $this->addChangesToConfirm();
        } else {
            $this->editPaymentMethod($params['payment'], $params['order_id']);
            $this->addChangesToLog();
            $this->notifyEmail();
        }
    }

    public function execUpdatePaymentMethod($paymentData, $orderId)
    {
        $this->editPaymentMethod($paymentData, $orderId);
        $this->notifyEmail();
        return true;
    }

    protected function editPaymentMethod($paymentData, $orderId)
    {
        try {
            if ($orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);

                if (!empty($order) && $order->getEntityId()) {
                    $oldPayment = $order->getPayment()->getMethodInstance()->getTitle();

                    if ($order->getPayment()->getMethod() == "iwd_authorizecim") {
                        $transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
                            ->addAttributeToFilter('order_id', array('eq' => $order->getEntityId()))
                            ->addAttributeToFilter('txn_type', array('eq' => Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH));

                        $cardId = $order->getPayment()->getIwdAuthorizecimCardId();
                        $method = $order->getPayment()->getMethodInstance();
                        $card = $method->loadCard($cardId);
                        $gateway = $method->gateway();
                        $gateway->setCard($card);

                        foreach ($transactions as $transaction) {
                            $txnId = $transaction->getTxnId();
                            $delimiter = strpos($txnId, "-");
                            $txnId = $delimiter ? substr($txnId, 0, $delimiter) : $txnId;

                            $gateway->void($order->getPayment(), $txnId);
                            $transaction->setOrderPaymentObject($order->getPayment());
                            $transaction->close(false)->setTxnType(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID)->save();
                        }
                    }

                    $payment = $order->getPayment();
                    $payment->addData($paymentData)->save();
                    $method = $payment->getMethodInstance();
                    $method->prepareSave()->assignData($paymentData);
                    $order->getPayment()->save();
                    $order->getPayment()->getOrder()->save();

                    $order = Mage::getModel('sales/order')->load($orderId);
                    $payment = $order->getPayment();
                    $payment->addData($paymentData)->save();
                    $payment->unsMethodInstance();
                    $method = $payment->getMethodInstance();
                    $method->prepareSave()->assignData($paymentData);

                    if ($order->getPayment()->getMethod() == "iwd_authorizecim") {
                        $cardId = $order->getPayment()->getIwdAuthorizecimCardId();
                        $order->place();
                        $order->getPayment()->setIwdAuthorizecimCardId($cardId);
                    } else {
                        $order->place();
                    }

                    $order->getPayment()->save();
                    $order->getPayment()->getOrder()->save();
                    $newPayment = Mage::getModel('sales/order')->load($orderId)->getPayment()->getMethodInstance()->getTitle();
                    Mage::getSingleton('iwd_ordermanager/logger')->addChangesToLog("payment_method", $oldPayment, $newPayment);

                    return 1;
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addNotice($e->getMessage());
            IWD_OrderManager_Model_Logger::log($e->getMessage());
            return -1;
        }

        return 0;
    }

    protected function notifyEmail()
    {
        $notify = isset($this->params['notify']) ? $this->params['notify'] : null;
        $orderId = $this->params['order_id'];

        if ($notify) {
            $message = isset($this->params['comment_text']) ? $this->params['comment_text'] : null;
            $email = isset($this->params['comment_email']) ? $this->params['comment_email'] : null;
            Mage::getModel('iwd_ordermanager/notify_notification')->sendNotifyEmail($orderId, $email, $message);
        }
    }

    protected function addChangesToLog()
    {
        $logger = Mage::getSingleton('iwd_ordermanager/logger');
        $orderId = $this->params['order_id'];

        $logger->addCommentToOrderHistory($orderId);
        $logger->addLogToLogTable(IWD_OrderManager_Model_Confirm_Options_Type::PAYMENT, $orderId);
    }

    protected function addChangesToConfirm()
    {
        $this->estimatePaymentMethod($this->params['order_id'], $this->params['payment']);

        Mage::getSingleton('iwd_ordermanager/logger')->addLogToLogTable(IWD_OrderManager_Model_Confirm_Options_Type::PAYMENT, $this->params['order_id'], $this->params);

        $message = Mage::helper('iwd_ordermanager')
            ->__('Order update not yet applied. Customer has been sent an email with a confirmation link. Updates will be applied after confirmation.');

        Mage::getSingleton('adminhtml/session')->addNotice($message);
    }


    public function isAllowEditPayment()
    {
        return Mage::getSingleton('admin/session')->isAllowed('iwd_ordermanager/order/actions/edit_payment');
    }

    public function isPaymentAllowedForReauthorize($order)
    {
        $payment = $order->getPayment();
        $orderMethod = $payment->getMethod();

        return in_array(
            $orderMethod,
            array(
                'authorizenet',
                'iwd_authorizecim',
                'iwd_authorizecim_echeck',
                Mage_Paypal_Model_Config::METHOD_PAYFLOWPRO
            )
        );
    }

    public function reauthorizePayment($orderId, $oldOrder)
    {
        if (self::IS_REAUTHORIZATION_ENABLED == false) {
            return 1;
        }

        try {
            $order = Mage::getModel('sales/order')->load($orderId);
            $payment = $order->getPayment();
            $orderMethod = $payment->getMethod();

            $oldAmountAuthorize = $payment->getBaseAmountAuthorized();
            $amount = $order->getBaseGrandTotal();

            // authorized (but do not captured) more then we need now (authorized $100, need $80)
            if (!$order->hasInvoices() && $oldAmountAuthorize >= $amount) {
                return 1;
            }

            switch ($orderMethod) {
                case 'free':
                case 'checkmo':
                case 'purchaseorder':
                    return 1;

                case 'authorizenet':
                    return $this->reauthorizeAuthorizeNet($order, $oldOrder);

                case 'iwd_authorizecim':
                case 'iwd_authorizecim_echeck':
                    return $this->reauthorizeIWDAuthorizeNetCIM($order, $oldOrder);

                case Mage_Paypal_Model_Config::METHOD_PAYFLOWPRO:
                    return $this->reauthorizePayPalPayflowPro($order);


                /* *
                 * Paradox Labs Authorize.net CIM
                 * Please, do not uncomment this code. It will not enable re-authorization via Paradox Labs Authorize.net CIM.
                 * You need add changes to Paradox Labs Authorize.net CIM extension for correct work of Order Manager
                 * */
                /*case 'authnetcim':
                    return $this->reauthorizeParadoxAuthorizeNetCIM($order, $oldOrder);*/

                default:
                    return 1;
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__($e->getMessage()));
            IWD_OrderManager_Model_Logger::log($e->getMessage(), true);
            return -1;
        }
    }


    /* * * * Authorize.net * * * */
    protected function reauthorizeAuthorizeNet($order, $oldOrder)
    {
        /* @var $paymentAuthorizenet IWD_OrderManager_Model_Payment_Authorizenet */
        /* @var $oldOrder Mage_Sales_Model_Order */
        /* @var $order Mage_Sales_Model_Order */

        $amount = $order->getGrandTotal();
        $payment = $order->getPayment();
        $authorized = $payment->getBaseAmountAuthorized();

        $paymentAuthorizenet = Mage::getModel('iwd_ordermanager/payment_authorizenet');

        // authorized (but do not captured) more then we need now (authorized $100, need $80)
        if (!$order->hasInvoices() && $authorized < $amount) {
            // void + authorize
            $paymentAuthorizenet->voidAuthorizeNet($order);
            return $paymentAuthorizenet->authorizeAuthorizeNet($order, $amount);
        }

        $new_total = $order->getGrandTotal() - $order->getBaseTotalRefunded();
        $old_total = $oldOrder->getGrandTotal() - $oldOrder->getBaseTotalRefunded();

        if ($old_total > $new_total) {
            // amount was decreased (-)
            $refund_amount = $old_total - $new_total;
            return $this->refundAuthorizenetLogic($order, $refund_amount);
        } else {
            // amount was increased (+)
            $additional_amount = $new_total - $old_total;
            return $paymentAuthorizenet->captureAuthorizeNet($order, $additional_amount);
        }
    }

    protected function refundAuthorizenetLogic($order, $refund_amount)
    {
        $transactions = $this->preparePaymentTransactions($order);

        /* @var $paymentAuthorizenet IWD_OrderManager_Model_Payment_Authorizenet */
        $paymentAuthorizenet = Mage::getModel('iwd_ordermanager/payment_authorizenet');

        $captured = array_sum($transactions['captured']);
        $settled = array_sum($transactions['settled']);

        if ($captured > $refund_amount) {
            foreach ($transactions['captured'] as $trx_id => $trx_amount) {
                $paymentAuthorizenet->voidAuthorizeNetTransaction($order, $trx_id);

                $refund_amount -= $trx_amount;
                if ($refund_amount == 0) {
                    return 1;
                }
                if ($refund_amount < 0) {
                    $capture_amount = abs($refund_amount);
                    return $paymentAuthorizenet->captureAuthorizeNet($order, $capture_amount);
                }
            }

            throw new Exception("We can not refund more than captured");
        } else {
            if ($captured + $settled > $refund_amount) {
                // VOID ALL CAPTURED TRANSACTIONS
                foreach ($transactions['captured'] as $trx_id => $trx_amount) {
                    if ($refund_amount == 0) {
                        return 1;
                    }

                    $result = $paymentAuthorizenet->voidAuthorizeNetTransaction($order, $trx_id);
                    if (is_string($result)) {
                        Mage::getSingleton('adminhtml/session')->addError($result);
                    }
                    $refund_amount -= $trx_amount;
                }

                // REFUND SETTLED TRANSACTIONS
                foreach ($transactions['settled'] as $trx_id => $trx_amount) {
                    if ($refund_amount == 0) {
                        return 1;
                    }

                    if ($refund_amount < $trx_amount) {
                        $refund = $refund_amount;
                        $refund_amount = 0;
                    } else {
                        $refund = $trx_amount;
                        $refund_amount -= $trx_amount;
                    }

                    $result = $paymentAuthorizenet->refundAuthorizeNet($order, $trx_id, $refund);
                    if (is_string($result)) {
                        Mage::getSingleton('adminhtml/session')->addError($result);
                    }
                }

                return 0;
            } else {
                throw new Exception("We can not refund more than captured");
            }
        }
    }

    protected function preparePaymentTransactions($order)
    {
        $transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addFieldToFilter('order_id', $order->getId());

        /* @var $paymentAuthorizenet IWD_OrderManager_Model_Payment_Authorizenet */
        $paymentAuthorizenet = Mage::getModel('iwd_ordermanager/payment_authorizenet');

        $transactionsCaptured = array();
        $transactionsSettled = array();
        foreach ($transactions as $transaction) {
            $trxInfo = $paymentAuthorizenet->fetchTrxInfo($transaction->getTransactionId());
            $id = $trxInfo->getData('transaction_id');

            $information = $trxInfo->getData('additional_information');
            $status = false;
            if (isset($information["raw_details_info"]["transaction_status"])) {
                $status = $information["raw_details_info"]["transaction_status"];
            }
            $auth_amount = 0;
            if (isset($information["raw_details_info"]["auth_amount"])) {
                $auth_amount = $information["raw_details_info"]["auth_amount"];
            }

            if ($status == "capturedPendingSettlement") {
                $transactionsCaptured[$id] = $auth_amount;
            }

            if ($status == "settledSuccessfully" && $transaction->getTxnType() == 'capture') {
                $transactionsSettled[$id] = $auth_amount;
            }
        }

        return array(
            'captured' => $transactionsCaptured,
            'settled' => $transactionsSettled,
        );
    }


    /* * * * IWD Authorize.net CIM * * * */
    protected function reauthorizeIWDAuthorizeNetCIM($order, $oldOrder)
    {
        /* @var $oldOrder Mage_Sales_Model_Order */
        /* @var $order Mage_Sales_Model_Order */
        $amount = $order->getGrandTotal();
        $payment = $order->getPayment();
        $authorized = $payment->getBaseAmountAuthorized();
        $paymentAuthorizenet = $payment->getMethodInstance();
        $new_total = $order->getGrandTotal() - $order->getBaseTotalRefunded();
        $old_total = $oldOrder->getGrandTotal() - $oldOrder->getBaseTotalRefunded();

        // authorized (but do not captured) more then we need now (authorized $100, need $80)
        if (!$order->hasInvoices() && $authorized < $amount) {
            $additional_amount = $new_total - $old_total;
            if (!$paymentAuthorizenet->authorize($payment, $additional_amount)) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__("Error in re-authorizing payment."));
                return 0;
            }
            $this->savePayment($payment);
            return 1;
        }

        if ($old_total > $new_total) {
            // amount was decreased (-)
            $refund_amount = $old_total - $new_total;
            return $this->refundAuthorizenetNetCIMLogic($order, $refund_amount);
        } else {
            // amount was increased (+)
            Mage::register('iwd_order_manager_authorize', true);
            $additional_amount = $new_total - $old_total;
            $paymentAuthorizenet->capture($payment, $additional_amount, true);
            $this->savePayment($payment);
            return 1;
        }
    }

    protected function savePayment($payment)
    {
        $payment->getOrder()->save();
        $payment->save();
    }

    protected function refundAuthorizenetNetCIMLogic($order, $refund_amount)
    {
        $transactions = $this->preparePaymentNetCIMTransactions($order);
        $payment = $order->getPayment();
        $paymentAuthorizenet = $payment->getMethodInstance();

        $captured = array_sum($transactions['captured']);
        $settled = array_sum($transactions['settled']);

        if ($captured > $refund_amount) {
            foreach ($transactions['captured'] as $trx_id => $trx_amount) {
                $paymentAuthorizenet->void($payment, $trx_id);

                $refund_amount -= $trx_amount;
                if ($refund_amount == 0) {
                    $this->savePayment($payment);
                    return 1;
                }
                if ($refund_amount < 0) {
                    $capture_amount = abs($refund_amount);
                    $paymentAuthorizenet->capture($payment, $capture_amount, true);
                    $this->savePayment($payment);
                    return 1;
                }
            }

            throw new Exception("We can not refund more than captured");
        } else {
            if ($captured + $settled > $refund_amount) {
                // VOID ALL CAPTURED TRANSACTIONS
                foreach ($transactions['captured'] as $trx_id => $trx_amount) {
                    if ($refund_amount == 0) {
                        return 1;
                    }

                    $paymentAuthorizenet->void($payment, $trx_id);
                    $refund_amount -= $trx_amount;
                }

                // REFUND SETTLED TRANSACTIONS
                foreach ($transactions['settled'] as $trx_id => $trx_amount) {
                    if ($refund_amount == 0) {
                        $this->savePayment($payment);
                        return 1;
                    }

                    if ($refund_amount < $trx_amount) {
                        $refund = $refund_amount;
                        $refund_amount = 0;
                    } else {
                        $refund = $trx_amount;
                        $refund_amount -= $trx_amount;
                    }

                    $result = $paymentAuthorizenet->refund($payment, $refund, $trx_id);
                    if (is_string($result)) {
                        Mage::getSingleton('adminhtml/session')->addError($result);
                    }
                }

                $this->savePayment($payment);
                return 1;
            } else {
                throw new Exception("We can not refund more than captured");
            }
        }
    }

    protected function preparePaymentNetCIMTransactions($order)
    {
        $payment = $order->getPayment();

        $transactions = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addFieldToFilter('order_id', $order->getId());

        /* @var $paymentAuthorizenet IWD_OrderManager_Model_Payment_Authorizenet */
        $paymentAuthorizenet = $payment->getMethodInstance()->gateway();

        $transactionsCaptured = array();
        $transactionsSettled = array();
        foreach ($transactions as $transaction) {
            $txnId = $transaction->getTxnId();
            $delimiter = strpos($txnId, "-");
            $txnId = $delimiter ? substr($txnId, 0, $delimiter) : $txnId;

            $trxInfo = $paymentAuthorizenet
                ->setParameter('transId', $txnId)
                ->getTransactionDetails();

            $id = $trxInfo['transaction']['transId'];

            $status = false;
            if (isset($trxInfo['transaction']["transactionStatus"])) {
                $status = $trxInfo['transaction']["transactionStatus"];
            }
            if ($status == "capturedPendingSettlement") {
                $transactionsCaptured[$id] = $trxInfo['transaction']["authAmount"];
            }
            if ($status == "settledSuccessfully" && $transaction->getTxnType() == 'capture') {
                $transactionsSettled[$id] = $trxInfo['transaction']["settleAmount"];
            }
        }

        return array(
            'captured' => $transactionsCaptured,
            'settled' => $transactionsSettled,
        );
    }

    /* * * * PayPal Payflow Pro Gateway * * * */
    protected function reauthorizePayPalPayflowPro($order)
    {
        $payment = $order->getPayment();
        $amount = $order->getGrandTotal();

        /* @var $method IWD_OrderManager_Model_Payment_Paypal_Payflowpro */
        $method = $payment->getMethodInstance()->setStore($order->getStoreId());

        if (!$method->reauthorize($payment, $amount)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__("Error in re-authorizing payment."));
            return -1;
        }

        $this->savePayment($payment);
        return 1;
    }


    public function GetActivePaymentMethods()
    {
        $payments = Mage::getModel('payment/config')->getActiveMethods();
        return $this->getMethodsTitle($payments);
    }

    public function getActivePaymentMethodsArray()
    {
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methods = array(array('value'=>'', 'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
        foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methods[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode,
            );
        }

        return $methods;
    }

    public function GetAllPaymentMethods()
    {
        $payments = Mage::getModel('payment/config')->getAllMethods();
        return $this->getMethodsTitle($payments);
    }

    private function getMethodsTitle($payments)
    {
        $methods = array();

        foreach ($payments as $paymentCode => $paymentModel) {
            $methods[$paymentCode] = Mage::getStoreConfig('payment/' . $paymentCode . '/title');
        }

        return $methods;
    }

    public function GetPaymentMethods()
    {
        $resource = Mage::getSingleton('core/resource')->getConnection('core_read');
        $tableName = Mage::getSingleton('core/resource')->getTableName('sales/order_payment');
        $results = $resource->fetchAll("SELECT DISTINCT `method` FROM `{$tableName}`");

        $methods = array();

        foreach ($results as $paymentCode) {
            $code = $paymentCode['method'];
            $methods[$code] = Mage::getStoreConfig('payment/' . $code . '/title');
        }

        return $methods;
    }


    public function canUpdatePaymentMethod($orderId)
    {
        $order = Mage::getModel('sales/order')->load($orderId);
        if (empty($order)) {
            return false;
        }

        return !$order->hasInvoices();
    }

    public function estimatePaymentMethod($orderId, $paymentData)
    {
        $order = Mage::getModel('sales/order')->load($orderId);

        $oldPayment = $order->getPayment()->getMethodInstance()->getTitle();
        $newPayment = Mage::helper('payment')->getMethodInstance($paymentData['method'])->getTitle();

        $totals = array(
            'grand_total' => $order->getGrandTotal(),
            'base_grand_total' => $order->getBaseGrandTotal(),
        );

        $log = Mage::getSingleton('iwd_ordermanager/logger');
        $log->addNewTotalsToLog($totals);
        $log->addChangesToLog("payment_method", $oldPayment, $newPayment);
        $log->addCommentToOrderHistory($orderId, 'wait');
    }

    /* * * * Paradox Lab Authorize.net CIM * * * */
    protected function reauthorizeParadoxAuthorizeNetCIM($order, $oldOrder)
    {
        $payment = $order->getPayment();

        $taxAmount = $order->getTaxAmount();
        $baseTaxAmount = $order->getBaseTaxAmount();

        $totalDue = $order->getGrandTotal() - $order->getTotalRefunded() - $payment->getAmountAuthorized();
        $baseTotalDue = $order->getBaseGrandTotal() - $order->getBaseTotalRefunded() - $payment->getBaseAmountAuthorized();

        $newTaxAmount = $order->getTaxAmount() - $oldOrder->getTaxAmount();
        $newBaseTaxAmount = $order->getBaseTaxAmount() - $oldOrder->getBaseTaxAmount();

        $newShippingAmount = $order->getShippingAmount() - $oldOrder->getShippingAmount();
        $newBaseShippingAmount = $order->getBaseShippingAmount() - $oldOrder->getBaseShippingAmount();

        $oldAmountAuthorize = $payment->getAmountAuthorized();
        $oldBaseAmountAuthorize = $payment->getBaseAmountAuthorized();

        $oldAmountPaid = $payment->getAmountPaid();
        $oldBaseAmountPaid = $payment->getBaseAmountPaid();


        if (isset($oldAmountPaid) && !empty($oldAmountPaid)) {
            $totalDue = $order->getGrandTotal() - $order->getTotalRefunded() - $payment->getAmountPaid();
            $baseTotalDue = $order->getBaseGrandTotal() - $order->getBaseTotalRefunded() - $payment->getBaseAmountPaid();
        }

        // capture
        if ($baseTotalDue > 0) {
            $newTaxAmount = $newTaxAmount > 0 ? $newTaxAmount : 0;
            $payment->getOrder()->setTaxAmount($newTaxAmount);

            $newTaxAmount = $newBaseTaxAmount > 0 ? $newBaseTaxAmount : 0;
            $payment->getOrder()->setBaseTaxAmount($newTaxAmount);

            $newShippingAmount = $newShippingAmount > 0 ? $newShippingAmount : 0;
            $payment->setShippingAmount($newShippingAmount);

            $newBaseShippingAmount = $newBaseShippingAmount > 0 ? $newBaseShippingAmount : 0;
            $payment->setBaseShippingAmount($newBaseShippingAmount);

            $payment->setAmountPaid($oldAmountAuthorize);
            $payment->setBaseAmountPaid($oldAmountAuthorize);

            Mage::register('iwd_order_manager_authorize', true);

            if (!$payment->authorize(1, $baseTotalDue)) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('iwd_ordermanager')->__("Error in re-authorizing payment."));
                return -1;
            }

            if (empty($oldAmountPaid)) {
                $payment->setAmountPaid($oldAmountPaid);
                $payment->setBaseAmountPaid($oldBaseAmountPaid);
            }

            if ($payment->getAmountAuthorized() < $payment->getAmountPaid()) {
                $payment->setAmountAuthorized($payment->getAmountPaid());
                $payment->setBaseAmountAuthorized($payment->getBaseAmountPaid());
            } else {
                $payment->setAmountAuthorized($oldAmountAuthorize + $totalDue);
                $payment->setBaseAmountAuthorized($oldBaseAmountAuthorize + $baseTotalDue);
            }
        } // refund
        else if ($baseTotalDue < 0 && $payment->getOrder()->getBaseTotalPaid() > 0) {
            Mage::register('iwd_order_manager_authorize', true);

            $refund = abs($baseTotalDue);
            $payment->getMethodInstance()->refund($payment, $refund);
        }

        $payment->setAmountOrdered($payment->getAmountOrdered() + $totalDue);
        $payment->setBaseAmountOrdered($payment->getBaseAmountOrdered() + $baseTotalDue);

        $payment->setShippingAmount($order->getShippingAmount());
        $payment->setBaseShippingAmount($order->getBaseShippingAmount());

        $payment->save();

        $order->setBaseTaxAmount($baseTaxAmount)->setTaxAmount($taxAmount)->save();

        return 1;
    }
}