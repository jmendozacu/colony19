<?php

class IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
    protected function _prepareCollection()
    {
        $filter = $this->prepareFilters();
        $collection = Mage::getResourceModel("sales/order_grid_collection");
        $collection = Mage::getModel('iwd_ordermanager/order_grid')->prepareCollection($filter, $collection);

        $this->setCollection($collection);
        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();

        $this->getOrderManagerTotals();

        return $this;
    }

    protected function _prepareColumns()
    {
        $selectedColumns = null;
        if (!Mage::helper("iwd_ordermanager")->enableCustomGrid()) {
            $selectedColumns = array(
                'increment_id',
                'store_id',
                'created_at',
                'billing_name',
                'shipping_name',
                'base_grand_total',
                'grand_total',
                'status',
                'action'
            );
        }

        $helper = Mage::helper('iwd_ordermanager');

        $grid = Mage::getModel('iwd_ordermanager/order_grid')->prepareColumns($this, $selectedColumns);
        $grid = Mage::getModel('iwd_ordermanager/order_grid')->addHiddenColumnWithStatus($grid);

        $grid->addRssList('rss/order/new', $helper->__('New Order RSS'));
        $grid->addExportType('*/*/exportCsv', $helper->__('CSV'));
        $grid->addExportType('*/*/exportExcel', $helper->__('Excel XML'));
        $grid->sortColumnsByOrder();

        return $grid;
    }

    protected function prepareFilters()
    {
        $filter = $this->getParam($this->getVarNameFilter(), null);

        if (is_null($filter)) {
            $filter = $this->_defaultFilter;
        }

        if (is_string($filter)) {
            $filter = $this->helper('adminhtml')->prepareFilterString($filter);
        }

        return $filter;
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('*/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function _toHtml()
    {
        return parent::_toHtml() . $this->getJsInitScripts();
    }

    protected function getJsInitScripts()
    {
        return $this->_getChildHtml('iwd_om.order.grid.jsinit');
    }

    public function getOrderManagerTotals()
    {
        /**
         * @var $totals IWD_OrderManager_Model_Order_Totals
         */
        $totals = Mage::getModel('iwd_ordermanager/order_totals');

        $collection = $this->getCollection();
        $totals->prepareTotals($collection);
    }
}
