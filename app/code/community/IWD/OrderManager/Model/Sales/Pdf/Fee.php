<?php

class IWD_OrderManager_Model_Sales_Pdf_Fee extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    /**
     * Get array of arrays with totals information for display in PDF
     * array(
     *  $index => array(
     *      'amount'   => $amount,
     *      'label'    => $label,
     *      'font_size'=> $font_size
     *  )
     * )
     * @return array
     */
    public function getTotalsForDisplay()
    {
        if ($this->getOrder()->getIwdOmFeeAmount() == 0) {
            return array();
        }

        $amount = $this->getOrder()->formatPriceTxt($this->getOrder()->getIwdOmFeeAmount());
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        $label = $this->getOrder()->getIwdOmFeeDescription();

        $totals = array(array(
            'amount'     => $this->getAmountPrefix() . $amount,
            'label'      => $label . ':',
            'font_size'  => $fontSize,
        ));

        return $totals;
    }
}
