<?php

class IWD_OrderManager_Block_Sales_Order_Total_Fee extends Mage_Core_Block_Abstract
{
    /**
     * Get Source Model
     * @return mixed
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Add this total to parent
     */
    public function initTotals()
    {
        $amount = $this->getSource()->getIwdOmFeeAmount();

        if ($amount != 0) {
            $label = $this->getOrder()->getIwdOmFeeDescription();
            $label = empty($label) ? $this->__('Custom Amount') : $label;
            $total = new Varien_Object(array(
                'code'  => 'iwd_om_fee',
                'field' => 'iwd_om_fee_amount',
                'value' => $amount,
                'label' => $label
            ));
            $this->getParentBlock()->addTotalBefore($total, array('subtotal_excl', 'subtotal_incl', 'subtotal'));
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        $source = $this->getSource();
        if ($source instanceof Mage_Sales_Model_Order) {
            return $source;
        }

        return $source->getOrder();
    }
}
