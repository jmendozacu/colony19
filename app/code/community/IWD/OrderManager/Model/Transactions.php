<?php

class IWD_OrderManager_Model_Transactions extends Mage_Core_Model_Abstract
{
    protected $collection = null;

    protected $methods = array(
        'authorizenet',            /* standard Authorize.net */
        'iwd_authorizecim',        /* IWD Authorize.net CIM */
        'iwd_authorizecim_echeck', /* IWD Authorize.net eCheck*/
        //'authnetcim',            /* ParadoxLabs Authorize.net CIM */
        //'authnetcim_ach',        /* ParadoxLabs Authorize.net CIM */
        //...
    );

    public function _construct()
    {
        $this->_init('iwd_ordermanager/transactions');
    }

    public function refresh($for_email = false)
    {
        $collection = $this->getCollectionForRefresh();

        /* add filter for reduce load time */
        $collection = $this->addPeriodFilter($collection, $for_email);

        foreach ($collection as $mage_transaction) {
            $auth_transaction = (array)$this->getTransactionDetails($mage_transaction->getNormalTxnId(), $mage_transaction->getStoreId());
            if (!empty($auth_transaction)) {
                $this->saveTransactionData($auth_transaction, $mage_transaction);
            }
        }

        Mage::getModel('core/flag', array('flag_code' => 'iwd_settlementreport_transactions'))->loadSelf()
            ->setState(0)
            ->save();
    }

    protected function getCollectionForRefresh()
    {
        $tableName_sales_flat_order_payment = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_payment');
        $tableName_sales_flat_order = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');

        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addFieldToSelect(array(
                'normal_txn_id' => new Zend_Db_Expr("SUBSTRING_INDEX(`main_table`.`txn_id`, '-', 1)"),
                'created_at' => 'created_at',
                'txn_type' => new Zend_Db_Expr('group_concat(DISTINCT `main_table`.`txn_type` SEPARATOR ",")'),
                'order_id' => 'order_id',
                'mage_amount' => 'amount'
            ));

        $collection->getSelect()->joinLeft($tableName_sales_flat_order_payment,
            "main_table.payment_id = {$tableName_sales_flat_order_payment}.entity_id",
            array(
                'mage_amount_captured' => 'base_amount_paid',
                'mage_amount_settlement' => 'base_amount_paid',
                'mage_amount_refunded' => 'base_amount_refunded',
                'mage_amount_authorized' => 'base_amount_authorized',
                'method' => 'method'
            )
        );

        $collection->addFieldToFilter('method', array('in' => $this->methods));

        $collection->getSelect()->joinLeft($tableName_sales_flat_order,
            "main_table.order_id = {$tableName_sales_flat_order}.entity_id",
            array(
                'store_id' => 'store_id',
                'order_increment_id' => 'increment_id'
            )
        );

        $collection->getSelect()->group("normal_txn_id");
        //echo $collection->getSelect(); die;

        return $collection;
    }

    protected function addPeriodFilter($collection, $for_email)
    {
        $limit_period = Mage::getStoreConfig('iwd_settlementreport/default/limit_period');
        $last_days = Mage::getStoreConfig('iwd_settlementreport/emailing/last_days');

        if (($for_email && $last_days != 0) || $limit_period) {
            $from = null;
            $to = null;

            if ($limit_period) {
                $filter_from = Mage::getSingleton('adminhtml/session')->getData("iwd_settlementreport_filter_from");
                $filter_to = Mage::getSingleton('adminhtml/session')->getData("iwd_settlementreport_filter_to");
                if (isset($filter_from) && isset($filter_to)) {
                    $from = DateTime::createFromFormat('m/d/Y', $filter_from)->modify('-1 day')->format('Y-m-d');
                    $to = DateTime::createFromFormat('m/d/Y', $filter_to)->modify('+1 day')->format('Y-m-d');
                }
            } elseif ($for_email) {
                $from = new DateTime();
                $last_days++;
                $from = $from->modify("-{$last_days} day")->format('Y-m-d');

                $to = new DateTime();
                $to = $to->modify('+1 day')->format('Y-m-d');
            }

            if (isset($from) && isset($to) && !empty($from) && !empty($to)) {
                $from = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s', $from);
                $to = Mage::getModel('core/date')->gmtDate('Y-m-d H:i:s', $to);

                $collection->addFieldToFilter('main_table.created_at', array(
                    'from' => $from,
                    'to' => $to,
                    'date' => true,
                ));
            }
        }

        //echo $collection->getSelect(); die;
        return $collection;
    }

    protected function getPaymentTransactionByTxnId($txn_id)
    {
        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->addFieldToFilter('txn_id', array("like" => "{$txn_id}%"));

        $collection->getSelect()->order(new Zend_Db_Expr('`main_table`.`created_at` DESC'));

        if ($collection->getSize() > 0) {
            return $collection->getFirstItem();
        }

        return null;
    }

    protected function saveTransactionData($auth_transaction, $mage_transaction)
    {
        if (!isset($auth_transaction['transId'])) {
            return null;
        }
        $trans_id = $auth_transaction['transId'];

        $iwd_auth_payment_transaction = $this->loadTransactionByTransId($trans_id);

        if (isset($auth_transaction['transactionType'])) {
            $iwd_auth_payment_transaction->setData('transaction_type', $auth_transaction['transactionType']);
        }

        $transaction_status = null;

        if (isset($auth_transaction['transactionStatus'])) {
            $transaction_status = $auth_transaction['transactionStatus'];
            $iwd_auth_payment_transaction->setData('auth_transaction_status', $auth_transaction['transactionStatus']);
        }

        $payment_trans = $this->getPaymentTransactionByTxnId($trans_id);
        $txn_type = (!empty($payment_trans)) ? $payment_trans->getTxnType() : $mage_transaction->getTxnType();
        $txn_type = explode(',', $txn_type);
        $txn_type = array_pop($txn_type);

        /* Reset */
        $iwd_auth_payment_transaction->setData('mage_amount_authorized', NULL);
        $iwd_auth_payment_transaction->setData('mage_amount_settlement', NULL);
        $iwd_auth_payment_transaction->setData('mage_amount_captured', NULL);
        $iwd_auth_payment_transaction->setData('mage_amount_refund', NULL);
        $iwd_auth_payment_transaction->setData('auth_amount_authorized', NULL);
        $iwd_auth_payment_transaction->setData('auth_amount_settlement', NULL);
        $iwd_auth_payment_transaction->setData('auth_amount_captured', NULL);
        $iwd_auth_payment_transaction->setData('auth_amount_refund', NULL);

        /* Voided */
        if ($transaction_status == "voided" && $txn_type != "void") {
            $iwd_auth_payment_transaction->setData('mage_amount_authorized', $mage_transaction->getMageAmountAuthorized());
            $iwd_auth_payment_transaction->setData('mage_amount_captured', $mage_transaction->getMageAmountCaptured());
        }


        /* Refunded */
        if (isset($auth_transaction['authAmount']) && ($transaction_status == 'refundPendingSettlement' || $transaction_status == 'refundSettledSuccessfully')) {

            $authAmount = $auth_transaction['authAmount'];

            $iwd_auth_payment_transaction->setData('auth_amount_refund', $authAmount);
            if ($transaction_status == 'refundSettledSuccessfully' && isset($auth_transaction['settleAmount'])) {
                $iwd_auth_payment_transaction->setData('auth_amount_settlement', $auth_transaction['settleAmount']);
            }

            $credit_memos = Mage::getModel('sales/order_creditmemo')->getCollection()
                ->addFieldToSelect(
                    array(
                        "base_grand_total" => "base_grand_total",
                        "created_at" => "created_at",
                        "order_id" => "order_id"
                    )
                )
                ->addFieldToFilter('order_id', $mage_transaction->getOrderId());

            $mage_amount_refund = NULL;
            if ($credit_memos->getSize() == 1) {
                $mage_amount_refund = $credit_memos->getFirstItem()->getBaseGrandTotal();
            } else {
                /* by created at */
                $base_grand_total = array();

                $transaction_created_at = DateTime::createFromFormat('Y-m-d H:i:s', $mage_transaction->getCreatedAt());
                foreach ($credit_memos as $credit_memo) {
                    $credit_memo_created_at = DateTime::createFromFormat('Y-m-d H:i:s', $credit_memo->getCreatedAt());
                    $diff = $transaction_created_at->getTimestamp() - $credit_memo_created_at->getTimestamp();
                    if (abs($diff) < 30) {
                        $base_grand_total[] = $credit_memo->getBaseGrandTotal();
                    }
                }

                /* by total */
                if (count($base_grand_total) == 1) {
                    $mage_amount_refund = $base_grand_total[0];
                } elseif (count($base_grand_total) >= 2) {
                    if (in_array($authAmount, $base_grand_total)) {
                        $mage_amount_refund = $authAmount;
                    } else {
                        $mage_amount_refund = array_sum($base_grand_total);
                    }
                }
            }

            $amount = $mage_transaction->getMageAmount();
            $amount = !empty($amount) ? $amount : $mage_amount_refund;

            $iwd_auth_payment_transaction->setData('mage_amount_refund', $amount);
            $iwd_auth_payment_transaction->setData('mage_amount_settlement', $amount);
            $iwd_auth_payment_transaction->setData('mage_amount_captured', NULL);
        }


        /* Authorized / Captured / Settled */
        if ($transaction_status == 'authorizedPendingCapture' || $transaction_status == 'settledSuccessfully' || $transaction_status == 'capturedPendingSettlement') {
            $iwd_auth_payment_transaction->setData('auth_amount_authorized', $auth_transaction['authAmount']);

            if ($txn_type == 'authorization') {
                $amount = $mage_transaction->getMageAmount();
                $amount = !empty($amount) ? $amount : $mage_transaction->getMageAmountAuthorized();
                $iwd_auth_payment_transaction->setData('mage_amount_authorized', $amount);
            }

            if ($txn_type == 'capture') {
                $amount_capture = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addFieldToFilter('parent_txn_id', $mage_transaction->getNormalTxnId())
                    ->addFieldToFilter('txn_type', 'capture')
                    ->getFirstItem()->getAmount();

                $amount_capture = !empty($amount_capture) ? $amount_capture : $mage_transaction->getMageAmountCaptured();
                if ($amount_capture != 0) {
                    $iwd_auth_payment_transaction->setData('mage_amount_captured', $amount_capture);
                }

                $amount = Mage::getModel('sales/order_payment_transaction')->getCollection()
                    ->addFieldToFilter('txn_id', $mage_transaction->getNormalTxnId())
                    ->addFieldToFilter('txn_type', 'authorization')
                    ->getFirstItem()->getAmount();
                if ($amount != 0) {
                    $iwd_auth_payment_transaction->setData('mage_amount_authorized', $amount);
                } else if ($amount_capture != 0) {
                    $iwd_auth_payment_transaction->setData('mage_amount_authorized', $amount_capture);
                }
            }

            if ($transaction_status == 'settledSuccessfully') {
                if (isset($auth_transaction['settleAmount'])) {
                    $iwd_auth_payment_transaction->setData('auth_amount_settlement', $auth_transaction['settleAmount']);
                    $iwd_auth_payment_transaction->setData('auth_amount_captured', $auth_transaction['settleAmount']);
                }

                $amount = $mage_transaction->getMageAmount();
                $amount = !empty($amount) ? $amount : $mage_transaction->getMageAmountSettlement();
                if ($amount != 0) {
                    $iwd_auth_payment_transaction->setData('mage_amount_settlement', $amount);
                }
            }

            if ($transaction_status == 'capturedPendingSettlement') {
                if (isset($auth_transaction['settleAmount'])) {
                    $iwd_auth_payment_transaction->setData('auth_amount_captured', $auth_transaction['settleAmount']);
                }
                //$iwd_auth_payment_transaction->setData('mage_amount_settlement', $mage_transaction->getMageAmountSettlement());
            }
        }

        $iwd_auth_payment_transaction->setData('payment_transaction_id', $mage_transaction->getTransactionId());
        $iwd_auth_payment_transaction->setData('transaction_id', $trans_id);
        $iwd_auth_payment_transaction->setData('created_at', $mage_transaction->getCreatedAt());
        $iwd_auth_payment_transaction->setData('order_id', $mage_transaction->getOrderId());
        $iwd_auth_payment_transaction->setData('order_increment_id', $mage_transaction->getOrderIncrementId());

        $iwd_auth_payment_transaction->setData('mage_transaction_status', $txn_type);


        $iwd_auth_payment_transaction->save();

        $this->updateStatus($iwd_auth_payment_transaction, $mage_transaction);

        return $iwd_auth_payment_transaction;
    }

    protected function updateStatus($iwd_auth_payment_transaction)
    {
        $status = $this->checkAmountDifference($iwd_auth_payment_transaction);

        if ($status == 1) {
            $status = $this->checkTransactionStatusDifference($iwd_auth_payment_transaction) ? 1 : 0;
        }

        $iwd_auth_payment_transaction->setData('status', $status)->save();
    }

    protected function checkAmountDifference($auth_transaction)
    {
        if ($auth_transaction->getData('auth_amount_authorized') != $auth_transaction->getData('mage_amount_authorized') ||
            $auth_transaction->getData('auth_amount_captured') != $auth_transaction->getData('mage_amount_captured') ||
            $auth_transaction->getData('auth_amount_settlement') != $auth_transaction->getData('mage_amount_settlement') ||
            $auth_transaction->getData('auth_amount_refund') != $auth_transaction->getData('mage_amount_refund')
        ) {
            return 0;
        }

        return 1;
    }

    protected function checkTransactionStatusDifference($transaction)
    {
        $status = $transaction->getData('auth_transaction_status');

        switch ($transaction->getData('mage_transaction_status')) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT:
                return false; /* I don't know when this status uses */
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                return ($status == "authorizedPendingCapture"); /* Pending approval on gateway */
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                return ($status == "authorizedPendingCapture");
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                return ($status == "capturedPendingSettlement" || $status == "settledSuccessfully");
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID:
                return ($status == "voided");
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                return ($status == "refundSettledSuccessfully" || $status == "refundPendingSettlement");
        }

        return false;
    }

    public function loadTransactionByTransId($trans_id)
    {
        $auth_transaction = Mage::getModel('iwd_ordermanager/transactions')->getCollection()
            ->addFieldToFilter('transaction_id', $trans_id);

        if ($auth_transaction->getSize() > 0) {
            return $auth_transaction->getFirstItem();
        }

        return Mage::getModel('iwd_ordermanager/transactions');
    }

    protected function getTransactionDetails($id, $store_id)
    {
        $details = Mage::getModel('iwd_ordermanager/authorize_authorizeNet')
            ->initConnection($store_id)
            ->getTransactionDetails($id);
        return (array)$details->xml->transaction;
    }

    public function getTransactionsCollection()
    {
        if (!empty($this->collection)) {
            return $this->collection;
        }

        $collection = $this->getCollection()
            ->addFieldToSelect(array(
                'auth_amount_captured' => 'auth_amount_captured',
                'auth_amount_settlement' => 'auth_amount_settlement',
                'auth_amount_refund' => 'auth_amount_refund',
                'auth_amount_authorized' => 'auth_amount_authorized',

                'mage_amount_captured' => 'mage_amount_captured',
                'mage_amount_settlement' => 'mage_amount_settlement',

                'mage_amount_refund' => 'mage_amount_refund',
                'mage_amount_authorized' => 'mage_amount_authorized',

                'transaction_type' => 'transaction_type',
                'auth_transaction_status' => 'auth_transaction_status',
                'mage_transaction_status' => 'mage_transaction_status',

                'status' => 'status',
                'payment_transaction_id' => 'payment_transaction_id',
                'transaction_id' => 'transaction_id',

                'order_id' => 'order_id',
                'order_increment_id' => 'order_increment_id',

                'created_at' => 'created_at',
            ));

        $this->collection = $collection;

        return $this->collection;
    }
}
