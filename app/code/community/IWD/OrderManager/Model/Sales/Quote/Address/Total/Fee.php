<?php

class IWD_OrderManager_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $_code = 'iwd_om_fee';

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this
     */
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        if (!$this->isAdminArea()) {
            return $this;
        }

        $this->_setAmount(0);
        $this->_setBaseAmount(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        $quote = $address->getQuote();

        $address->setIwdOmFeeAmount($quote->getIwdOmFeeAmount());
        $address->setIwdOmFeeBaseAmount($quote->getIwdOmFeeBaseAmount());
        $address->setIwdOmFeeDescription($quote->getIwdOmFeeDescription());

        $address->setGrandTotal($address->getGrandTotal() + $address->getIwdOmFeeAmount());
        $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getIwdOmFeeBaseAmount());

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amount = $address->getIwdOmFeeAmount();

        if ($amount != 0) {
            $address->addTotal(array(
                'code' => $this->getCode(),
                'title' => $address->getIwdOmFeeDescription(),
                'value' => $amount
            ));
        }

        return $this;
    }

    /**
     * @return unknown
     */
    public function isAdminArea()
    {
        return Mage::app()->getStore()->isAdmin();
    }
}