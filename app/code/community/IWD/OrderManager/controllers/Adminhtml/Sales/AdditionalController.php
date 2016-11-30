<?php

class IWD_OrderManager_Adminhtml_Sales_AdditionalController extends IWD_OrderManager_Controller_Abstract
{
    /**
     * @var array
     */
    protected $result = array();

    /**
     * return void
     */
    public function applyFeeAction()
    {
        $this->result = array('status' => 1);

        try {
            $this->applyFee();
            $this->addLogInfo();
        } catch (Exception $e) {
            $this->result = array('status' => 0, 'error' => $e->getMessage());
        }

        $this->prepareResponse($this->result);
    }

    protected function addLogInfo()
    {
        $orderId = $this->getOrderId();
        $log = Mage::getSingleton('iwd_ordermanager/logger');

        $additionalAmount = $this->getAdditionalAmount();
        $additionalDescription = $this->getAdditionalDescription();
        if (empty($additionalAmount)) {
            $message = Mage::helper('iwd_ordermanager')->__('Custom order amount was removed');
        } else {
            $message = sprintf(Mage::helper(
                'iwd_ordermanager')->__('Custom order amount was applied: %s - %s'),
                $additionalDescription,
                Mage::helper('core')->currency($additionalAmount, true, false)
            );
        }
        $log->addToLog($message);
        $log->addCommentToOrderHistory($orderId, false);
    }

    /**
     * return void
     * @throws Exception
     */
    protected function applyFee()
    {
        $additionalAmount = $this->getAdditionalAmount();
        $additionalBaseAmount = $this->getAdditionalBaseAmount();
        $additionalDescription = $this->getAdditionalDescription();

        $oldOrder = $this->getOrder();
        $orderId = $this->getOrderId();

        $order = $this->getOrder();
        $oldAdditionalAmount = (float)$this->getOrder()->getIwdOmFeeAmount();
        $oldAdditionalBaseAmount = (float)$this->getOrder()->getIwdOmFeeAmount();

        if ($oldAdditionalAmount != $additionalAmount) {
            $grandTotal = $order->getGrandTotal();
            $grandTotal += $additionalAmount - $oldAdditionalAmount;

            $baseGrandTotal = $order->getBaseGrandTotal();
            $baseGrandTotal += $additionalBaseAmount - $oldAdditionalBaseAmount;

            if ($grandTotal < 0) {
                $additionalAmount += abs($grandTotal);
                $additionalBaseAmount += abs($baseGrandTotal);
                $grandTotal = 0;
                $baseGrandTotal = 0;
            }

            $order->setIwdOmFeeAmount($additionalAmount)
                ->setIwdOmFeeBaseAmount($additionalBaseAmount)
                ->setGrandTotal($grandTotal)
                ->setBaseGrandTotal($baseGrandTotal);
        }

        $order->setIwdOmFeeDescription($additionalDescription)
            ->save();

        Mage::getModel('iwd_ordermanager/order_edit')->updateOrderPayment($orderId, $oldOrder);
    }

    /**
     * @return float
     */
    protected function getAdditionalAmount()
    {
        return $this->getRequest()->getParam('amount', 0);
    }

    /**
     * @return float
     */
    protected function getAdditionalBaseAmount()
    {
        $additionalAmount = $this->getAdditionalAmount();

        $baseCurrencyCode = $this->getOrder()->getBaseCurrency();
        $currentCurrencyCode = $this->getOrder()->getOrderCurrency();

        $additionalBaseAmount = $additionalAmount;
        if ($baseCurrencyCode != $currentCurrencyCode) {
            $additionalBaseAmount = Mage::helper('directory')->currencyConvert($additionalAmount, $currentCurrencyCode, $baseCurrencyCode);
        }

        return round($additionalBaseAmount, 2);
    }

    /**
     * @return string
     */
    protected function getAdditionalDescription()
    {
        return $this->getRequest()->getParam('description', 'Custom Amount');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('iwd_ordermanager/order/actions/custom_amount');
    }
}
