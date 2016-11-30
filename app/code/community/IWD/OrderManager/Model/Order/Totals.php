<?php

class IWD_OrderManager_Model_Order_Totals extends Mage_Core_Model_Abstract
{
    /**
     * @var Varien_Data_Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $totals = array();

    /**
     * @var array
     */
    protected $gridOptions = array();

    /**
     * @param $collection Varien_Data_Collection
     */
    public function prepareTotals($collection)
    {
        if (!$this->isTotalsEnabled()) {
            return;
        }

        Mage::unregister('iwd_om_grid_totals');
        Mage::unregister('iwd_om_grid_options');

        $this->prepareTotalsForAllOrders($collection);
        $this->prepareTotalsForPage($collection);
        $this->prepareGridOptions($collection);

        Mage::register('iwd_om_grid_totals', $this->totals);
        Mage::register('iwd_om_grid_options', $this->gridOptions);
    }

    /**
     * @return bool
     */
    public function isTotalsEnabled()
    {
        return Mage::getStoreConfig('iwd_ordermanager/grid_order/order_totals_enable');
    }

    /**
     * @param $collection
     */
    public function prepareTotalsForAllOrders($collection)
    {
        $fields = array(
            'total' => new Zend_Db_Expr('SUM(base_grand_total)'),
            'subtotal' => new Zend_Db_Expr('SUM(base_subtotal)'),
            'shipped' => new Zend_Db_Expr('SUM(base_shipping_amount)'),
            'tax' => new Zend_Db_Expr('SUM(base_tax_amount)'),
            'invoiced' => new Zend_Db_Expr('SUM(base_total_invoiced)'),
            'discount' => new Zend_Db_Expr('SUM(base_discount_amount)'),
            'refunded' => new Zend_Db_Expr('SUM(base_total_refunded)'),
        );

        $select = clone $collection->getSelect();
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        $countSelect = clone $collection->getSelect();
        $countSelect->reset();
        $countSelect->from(array('a' => $select), $fields);

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchRow($countSelect);

        $totals = array_keys($fields);
        foreach ($totals as $total) {
            $this->addTotalAmount($total, $results[$total], 'amount');
        }
    }

    /**
     * @param $collection Varien_Data_Collection
     */
    public function prepareTotalsForPage($collection)
    {
        $fields = array(
            'total' => new Zend_Db_Expr('SUM(base_grand_total)'),
            'subtotal' => new Zend_Db_Expr('SUM(base_subtotal)'),
            'shipped' => new Zend_Db_Expr('SUM(base_shipping_amount)'),
            'tax' => new Zend_Db_Expr('SUM(base_tax_amount)'),
            'invoiced' => new Zend_Db_Expr('SUM(base_total_invoiced)'),
            'discount' => new Zend_Db_Expr('SUM(base_discount_amount)'),
            'refunded' => new Zend_Db_Expr('SUM(base_total_refunded)'),
        );

        $select = clone $collection->getSelect();
        $countSelect = clone $collection->getSelect();
        $countSelect->reset();
        $countSelect->from(array('a' => $select), $fields);

        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $results = $readConnection->fetchRow($countSelect);

        $totals = array_keys($fields);
        foreach ($totals as $total) {
            $this->addTotalAmount($total, $results[$total], 'page_amount');
        }
    }

    /**
     * @param $key
     * @param $amount
     * @param $type
     */
    protected function addTotalAmount($key, $amount, $type)
    {
        if (!isset($this->totals[$key])) {
            $this->totals[$key] = array();
        }

        $amount = empty($amount) ? 0 : $amount;
        $this->totals[$key][$type] = Mage::helper('core')->currency($amount, true, false);
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return Mage::registry('iwd_om_grid_totals');
    }

    /**
     * @param $collection Varien_Data_Collection
     */
    protected function prepareGridOptions($collection)
    {
        $pageSize = $collection->getPageSize();
        $getCurPage = $collection->getCurPage();
        $to = $pageSize * $getCurPage;
        $size = $collection->getSize();

        $this->gridOptions = array(
            'page_from' => $pageSize * ($getCurPage - 1) + 1,
            'page_to' => ($to > $size) ? $size : $to,
            'orders_count' => $size
        );
    }

    /**
     * @return array
     */
    public function getGridOptions()
    {
        return Mage::registry('iwd_om_grid_options');
    }
}
