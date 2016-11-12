<?php

class SDW_AdminPayment_Model_Observer
{
    private $_gift = 3;
    private $_amount = 100;

    public function sales_order_invoice_register($observer)
    {
        $order=$observer->getEvent()->getOrder();
        $code=$order->getPayment()->getMethodInstance()->getCode();
        if($code=="adminpayment1" || $code=="adminpayment2")
        {
            $state = Mage_Sales_Model_Order::STATE_PROCESSING;
            $order->setState($state, $state);
            $order->save();
        }
    }
}