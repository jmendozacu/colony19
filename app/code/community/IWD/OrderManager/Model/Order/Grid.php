<?php

class IWD_OrderManager_Model_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    const IS_LIMIT_PERIOD = false;      /* You can set period limit, if you have a lot of orders */
    const MAX_ORDERS_COUNT = 10000;
    const DEFAULT_PERIOD_IN_DAYS = 30;
    const MIN_DEFAULT_PERIOD_IN_DAYS = 2;

    const XML_PATH_ORDER_GRID_COLUMN = 'iwd_ordermanager/grid_order/columns';
    const XML_PATH_ORDER_GRID_FIX_HEADER = 'iwd_ordermanager/grid_order/fix_table_header';
    const XML_PATH_ORDER_GRID_STATUS_COLOR = 'iwd_ordermanager/grid_order/status_color';

    private $collection;
    private $selectedColumns;

    public function isLimitPeriod()
    {
        $selectedColumns = $this->getSelectedColumnsArray(self::XML_PATH_ORDER_GRID_COLUMN);
        $baseColumns = array('increment_id', 'status', 'store_id', 'grand_total', 'base_grand_total', 'created_at', 'updated_at', 'total_paid', 'shipping_name', 'billing_name');

        if (self::IS_LIMIT_PERIOD) {
            $base = array_diff($selectedColumns, $baseColumns);
            return !empty($base);
        }

        return self::IS_LIMIT_PERIOD;
    }

    public function getStatusColors()
    {
        return trim((string)Mage::getStoreConfig(self::XML_PATH_ORDER_GRID_STATUS_COLOR));
    }

    public function isFixGridHeader()
    {
        return Mage::getStoreConfig(self::XML_PATH_ORDER_GRID_FIX_HEADER);
    }

    public function getSelectedColumnsArray($gridXpath)
    {
        $selectedColumns = Mage::getStoreConfig($gridXpath);
        $selectedColumns = explode(",", $selectedColumns);

        if (self::IS_LIMIT_PERIOD && !isset($selectedColumns['created_at'])) {
            $selectedColumns[] = 'created_at';
        }

        return $selectedColumns;
    }

    public function prepareCollection($filter, $collection)
    {
        $selectedColumns = array();
        if (Mage::helper("iwd_ordermanager")->enableCustomGrid()) {
            $selectedColumns = $this->getSelectedColumnsArray(self::XML_PATH_ORDER_GRID_COLUMN);
        }

        $this->collection = $collection;

        $this->collection->addFieldToSelect(
            array('status', 'store_id', 'store_name', 'customer_id',
                'base_grand_total', 'base_total_paid', 'grand_total', 'total_paid', 'increment_id', 'base_currency_code',
                'order_currency_code', 'created_at', 'updated_at', 'shipping_name', 'billing_name'
            )
        );

        $this->collection = $this->getOrdersCollection($selectedColumns);

        if ($this->isLimitPeriod()) {
            $this->addFiltersToCollection($this->collection, $filter);
        }

        Mage::dispatchEvent('iwd_ordermanager_custom_order_collection_load_after');

        return $this->collection;
    }

    protected function addFiltersToCollection($collection, $filter)
    {
        if (isset($filter['created_at']) && isset($filter['created_at']['from']) && isset($filter['created_at']['to'])) {
            $from = date('Y-m-d', strtotime($filter['created_at']['from']));
            $to = date('Y-m-d', strtotime($filter['created_at']['to']));
        } else {
            $from = date("Y-m-d", strtotime("-" . self::DEFAULT_PERIOD_IN_DAYS . " days"));
            $to = date('Y-m-d');
        }

        $this->checkCollectionElementsCount($from, $to);
        $this->addPeriodFilterToSession($from, $to);

        $collection->getSelect()->where("main_table.created_at >= '{$from}'");
        $collection->getSelect()->where("main_table.created_at <= '{$to}'");

        return $collection;
    }

    protected function checkCollectionElementsCount(&$from, &$to)
    {
        $collection = Mage::getResourceModel('sales/order_grid_collection');
        $collection->getSelect()->where("main_table.created_at >= '{$from}'");
        $collection->getSelect()->where("main_table.created_at <= '{$to}'");

        if ($collection->getSize() >= self::MAX_ORDERS_COUNT) {
            $from = date("Y-m-d", strtotime("-" . self::MIN_DEFAULT_PERIOD_IN_DAYS . "days", strtotime($to)));
        }
    }

    protected function addPeriodFilterToSession($from, $to)
    {
        $sales_order_gridfilter = Mage::getSingleton('adminhtml/session')->getData("sales_order_gridfilter");
        $filter = Mage::helper('adminhtml')->prepareFilterString($sales_order_gridfilter);
        $filter['created_at']['from'] = date('m/d/Y', strtotime($from));
        $filter['created_at']['to'] = date('m/d/Y', strtotime($to));
        $filter['created_at']['locale'] = 'en_US';

        Mage::getSingleton('adminhtml/session')->setData("created_at_from", $filter['created_at']['from']);
        Mage::getSingleton('adminhtml/session')->setData("created_at_to", $filter['created_at']['to']);

        $filter = base64_encode(http_build_query($filter));
        Mage::getSingleton('adminhtml/session')->setData("sales_order_gridfilter", $filter);
    }

    public function getOrdersCollection($selectedColumns, $collection = null)
    {
        $this->selectedColumns = $selectedColumns;
        if ($collection !== null) {
            $this->collection = $collection;
        }

        $this->addOrderDetailsToSelect();

        if (Mage::helper("iwd_ordermanager")->enableCustomGrid()) {
            $this->addPaymentMethodToSelect();
            $this->addMultiInventoryToSelect();
            $this->addOrderItemsToSelect();
            $this->addBillingAddressToSelect();
            $this->addShippingAddressToSelect();
            $this->addInvoiceToSelect();
            $this->addCreditmemoToSelect();
            $this->addShipmentToSelect();
            $this->addTrackNumberToSelect();
            $this->addOrderCommentsToSelect();
            $this->addArchivedToSelect();
            $this->addOrderFlagsToSelect();
        }

        $this->collection->getSelect()->group('main_table.entity_id');

        //echo $this->collection->getSelect(); die;

        return $this->collection;
    }

    protected function addPaymentMethodToSelect()
    {
        $tableNameSalesOrderPayment = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_payment');
        if (in_array('payment_method', $this->selectedColumns)) {
            $this->collection->getSelect()->joinLeft(
                $tableNameSalesOrderPayment,
                "main_table.entity_id={$tableNameSalesOrderPayment}.parent_id",
                array('payment_method' => 'method')
            );
        }
    }

    protected function addOrderDetailsToSelect()
    {
        $tableNameSalesFlatOrder = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');

        $salesFlatOrder = array('shipping_description', 'customer_email', 'coupon_code', 'weight', 'customer_note');
        $selectedCol = array_intersect($this->selectedColumns, $salesFlatOrder);

        $additionalColumns = array(
            'base_subtotal', 'base_shipping_amount', 'base_tax_amount', 'base_total_invoiced', 'base_discount_amount', 'base_total_refunded',
            'subtotal', 'shipping_amount', 'tax_amount', 'total_invoiced', 'discount_amount', 'total_refunded'
        );
        $salesFlatOrder = array_merge($selectedCol, $additionalColumns);

        $this->collection->getSelect()->joinLeft(
            $tableNameSalesFlatOrder,
            "main_table.entity_id = {$tableNameSalesFlatOrder}.entity_id",
            $salesFlatOrder
        );
    }

    protected function addMultiInventoryToSelect()
    {
        if (in_array('inventory', $this->selectedColumns) && Mage::helper('iwd_ordermanager')->isMultiInventoryEnable()) {
            $tableNameIwdCataloginventoryStockOrder = Mage::getSingleton('core/resource')->getTableName('iwd_cataloginventory_stock_order');
            $this->collection->getSelect()->joinLeft(
                $tableNameIwdCataloginventoryStockOrder,
                "main_table.entity_id = {$tableNameIwdCataloginventoryStockOrder}.order_id",
                array(
                    'stock_qty_assigned' => 'qty_assigned',
                    'stock_qty_ordered' => 'qty_ordered',
                    'stock_assigned' => 'assigned',
                )
            );
        }
    }

    protected function addOrderItemsToSelect()
    {

        $selectedCol = array_intersect($this->selectedColumns, array('qty', 'product_sku', 'ordered_products', 'weight'));
        if (!empty($selectedCol)) {
            $tableNameSalesFlatOrderItem = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item');

            $this->collection->getSelect()->joinLeft(
                array('order_items' => $tableNameSalesFlatOrderItem),
                "main_table.entity_id = order_items.order_id",
                array('product_sku' => new Zend_Db_Expr('group_concat(DISTINCT order_items.sku SEPARATOR ", ")'),
                    'ordered_products' => new Zend_Db_Expr('group_concat(DISTINCT order_items.name SEPARATOR ", ")'),
                    'product_options' => new Zend_Db_Expr('group_concat(DISTINCT order_items.product_options SEPARATOR "|| ")'),
                    'qty_ordered', 'qty_invoiced', 'qty_shipped', 'qty_canceled', 'qty_refunded',
                ));
        }
    }

    protected function addBillingAddressToSelect()
    {

        $selectedCol = array_intersect($this->selectedColumns, array('billing_address', 'billing_company', 'billing_country', 'billing_state', 'billing_city', 'billing_street', 'billing_postcode', 'billing_telephone'));
        if (!empty($selectedCol)) {
            $tableNameSalesFlatOrderAddress = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_address');

            $this->collection->getSelect()->joinLeft(
                array('bill' => $tableNameSalesFlatOrderAddress),
                'main_table.entity_id = bill.parent_id AND bill.address_type="billing"',
                array('bill.street as billing_street', 'bill.city as billing_city', 'bill.region as billing_region',
                    'bill.postcode as billing_postcode', 'bill.telephone as billing_telephone', 'bill.fax as billing_fax',
                    'bill.company as billing_company', 'bill.country_id as billing_country_id'
                )
            );
        }
    }

    protected function addShippingAddressToSelect()
    {
        $selectedCol = array_intersect($this->selectedColumns, array('shipping_address', 'shipping_company', 'shipping_country', 'shipping_state', 'shipping_city', 'shipping_street', 'shipping_postcode', 'shipping_telephone'));
        if (!empty($selectedCol)) {
            $tableNameSalesFlatOrderAddress = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_address');
            $this->collection->getSelect()->joinLeft(
                array('ship' => $tableNameSalesFlatOrderAddress),
                'main_table.entity_id = ship.parent_id AND ship.address_type="shipping"',
                array('ship.street as shipping_street', 'ship.city as shipping_city', 'ship.region as shipping_region',
                    'ship.postcode as shipping_postcode', 'ship.telephone as shipping_telephone', 'ship.fax as shipping_fax',
                    'ship.company as shipping_company', 'ship.country_id as shipping_country_id'
                )
            );
        }
    }

    protected function addInvoiceToSelect()
    {
        if (in_array('invoice', $this->selectedColumns)) {
            $tableNameSalesFlatInvoice = Mage::getSingleton('core/resource')->getTableName('sales_flat_invoice');
            $this->collection->getSelect()->joinLeft(
                $tableNameSalesFlatInvoice,
                "main_table.entity_id = {$tableNameSalesFlatInvoice}.order_id",
                array('invoice' => new Zend_Db_Expr("group_concat(DISTINCT {$tableNameSalesFlatInvoice}.increment_id SEPARATOR \", \")"),
            ));
        }
    }

    protected function addCreditmemoToSelect()
    {
        if (in_array('creditmemo', $this->selectedColumns)) {
            $tableNameSalesFlatCreditmemo = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
            $this->collection->getSelect()->joinLeft(
                $tableNameSalesFlatCreditmemo,
                "main_table.entity_id = {$tableNameSalesFlatCreditmemo}.order_id",
                array('creditmemo' => new Zend_Db_Expr("group_concat(DISTINCT {$tableNameSalesFlatCreditmemo}.increment_id SEPARATOR \", \")"),
            ));
        }
    }

    protected function addShipmentToSelect()
    {
        if (in_array('shipment', $this->selectedColumns)) {
            $tableNameSalesFlatShipment = Mage::getSingleton('core/resource')->getTableName('sales_flat_shipment');
            $this->collection->getSelect()->joinLeft(
                $tableNameSalesFlatShipment,
                "main_table.entity_id = {$tableNameSalesFlatShipment}.order_id",
                array('shipment' => new Zend_Db_Expr("group_concat(DISTINCT {$tableNameSalesFlatShipment}.increment_id SEPARATOR \", \")"),
            ));
        }
    }

    protected function addTrackNumberToSelect()
    {
        if (in_array('track_number', $this->selectedColumns)) {
            $tableNameSalesFlatShipmentTrack = Mage::getSingleton('core/resource')->getTableName('sales_flat_shipment_track');
            $this->collection->getSelect()->joinLeft(
                $tableNameSalesFlatShipmentTrack,
                "main_table.entity_id = {$tableNameSalesFlatShipmentTrack}.order_id",
                array('track_number' => new Zend_Db_Expr("group_concat(DISTINCT {$tableNameSalesFlatShipmentTrack}.track_number SEPARATOR \", \")"),
            ));
        }
    }

    protected function addOrderCommentsToSelect()
    {
        if (in_array('order_comment', $this->selectedColumns)) {
            $tableNameOrderStatusHistory = Mage::getSingleton('core/resource')->getTableName('sales/order_status_history');
            $this->collection->getSelect()->joinLeft(
                array('ordercomment_table' => $tableNameOrderStatusHistory),
                'main_table.entity_id = ordercomment_table.parent_id AND ordercomment_table.comment IS NOT NULL',
                array('order_comment' => 'ordercomment_table.comment')
            )->group('main_table.entity_id');
        }
    }

    protected function addArchivedToSelect()
    {
        if (in_array('archived', $this->selectedColumns)) {
            $tableNameSalesFlatOrderGrid = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid');
            $this->collection->getSelect()->joinLeft(
                array('sales_flat_order_grid' => $tableNameSalesFlatOrderGrid),
                'main_table.entity_id = sales_flat_order_grid.entity_id',
                array('(sales_flat_order_grid.entity_id IS NULL) as archived')
            );
        }
    }

    protected function addOrderFlagsToSelect()
    {
        $tableNameIwdOmFlagsOrders = Mage::getSingleton('core/resource')->getTableName('iwd_om_flags_orders');
        $flagTypes = Mage::getModel('iwd_ordermanager/flags_types')->getCollection();
        foreach ($flagTypes as $type) {
            $orderGrid = $type->getOrderGridId();
            $this->collection->getSelect()->joinLeft(
                array($orderGrid => $tableNameIwdOmFlagsOrders),
                "main_table.entity_id = $orderGrid.order_id AND $orderGrid.type_id = {$type->getId()}",
                array("{$type->getOrderGridId()}" => 'flag_id')
            );
        }
    }

    public function getOrderGridColumns()
    {
        $helper = Mage::helper('iwd_ordermanager');

        $rows = array(
            /*** sales_flat_order_grid (base table) ***/
            'increment_id' => $helper->__('*Order #'),
            'status' => $helper->__('*Status'),
            'store_id' => $helper->__('*Purchased From (Store)'),
            'grand_total' => $helper->__('*G.T. (Purchased)'),
            'base_grand_total' => $helper->__('*G.T. (Base)'),
            'created_at' => $helper->__('*Created At (Purchased On)'),
            'updated_at' => $helper->__('*Updated At'),
            'total_paid' => $helper->__('*Total Paid'),
            'shipping_name' => $helper->__('*Ship - Name'),
            'billing_name' => $helper->__('*Bill - Name'),

            /*** sales_flat_order_item ***/
            'ordered_products' => $helper->__('Item(s) Ordered'),
            'product_sku' => $helper->__('Product Sku(s)'),
            'product_images' => $helper->__('Product Images'),

            'qty' => $helper->__('Product Quantity'),
            'payment_method' => $helper->__('Payment Method'),

            'weight' => $helper->__('Product Weight'),
            'shipping_description' => $helper->__('Shipping Method'),
            'coupon_code' => $helper->__('Coupon Code'),
            'customer_email' => $helper->__('Customer Email'),
            'customer_note' => $helper->__('Customer Note'),
            'order_comment' => $helper->__('Last Order Comment'),
            'order_comment_first' => $helper->__('First Order Comment'),

            'tax_amount' => $helper->__('Tax Amount'),
            'base_tax_amount' => $helper->__('Tax Amount (Base)'),
            'shipping_amount' => $helper->__('Ship. Amount'),
            'base_shipping_amount' => $helper->__('Ship. Amount (Base)'),
            'total_invoiced' => $helper->__('Invoiced Amount'),
            'base_total_invoiced' => $helper->__('Invoiced Amount (Base)'),
            'total_refunded' => $helper->__('Refunded Amount'),
            'base_total_refunded' => $helper->__('Refunded Amount (Base)'),
            'discount_amount' => $helper->__('Discount Amount'),
            'base_discount_amount' => $helper->__('Discount Amount (Base)'),
            'subtotal' => $helper->__('Subtotal Amount'),
            'base_subtotal' => $helper->__('Subtotal Amount (Base)'),

            /*** sales objects ***/
            'invoice' => $helper->__('Invoice(s)'),
            'shipment' => $helper->__('Shipment(s)'),
            'creditmemo' => $helper->__('Credit Memo(s)'),
            'track_number' => $helper->__('Track Number(s)'),

            /*** billing address ***/
            'billing_address' => $helper->__('Bill Address'),
            'billing_company' => $helper->__('Bill - Company'),
            'billing_country' => $helper->__('Bill - Country'),
            'billing_state' => $helper->__('Bill - State'),
            'billing_city' => $helper->__('Bill - City'),
            'billing_street' => $helper->__('Bill - Street'),
            'billing_postcode' => $helper->__('Bill - Postcode'),
            'billing_telephone' => $helper->__('Bill - Phone'),

            /*** shipping address ***/
            'shipping_address' => $helper->__('Ship Address'),
            'shipping_company' => $helper->__('Ship - Company'),
            'shipping_country' => $helper->__('Ship - Country'),
            'shipping_state' => $helper->__('Ship - State'),
            'shipping_city' => $helper->__('Ship - City'),
            'shipping_street' => $helper->__('Ship - Street'),
            'shipping_postcode' => $helper->__('Ship - Postcode'),
            'shipping_telephone' => $helper->__('Ship - Phone'),

            /*** actions ***/
            'action' => $helper->__('Action'),
            'reorder' => $helper->__('Reorder'),
            'archived' => $helper->__('Archived'),

            'inventory' => $helper->__('Inventory')
        );

        $flagTypes = Mage::getModel('iwd_ordermanager/flags_types')->getCollection();
        foreach ($flagTypes as $type) {
            $rows[$type->getOrderGridId()] = $helper->__('Label - ') . $type->getName();
        }

        return $rows;
    }

    public function prepareColumns(Mage_Adminhtml_Block_Widget_Grid $grid, $selectedColumns = null)
    {
        if ($selectedColumns === null) {
            $selectedColumns = $this->getSelectedColumnsArray(self::XML_PATH_ORDER_GRID_COLUMN);
        }

        $orderColumns = $this->prepareGridColumns();

        foreach ($selectedColumns as $column) {
            if (isset($orderColumns[$column])) {
                $grid->addColumn($column, $orderColumns[$column]);
            }
        }

        return $grid;
    }

    public function addHiddenColumnWithStatus($grid)
    {
        if (Mage::helper("iwd_ordermanager")->enableCustomGrid()) {
            $grid->addColumn('status-row', array(
                'index' => 'status',
                'type' => 'text',
                'filter' => false,
                'sortable' => false,
                'is_system' => true,
                'width' => '0px',
                'column_css_class' => 'no-display status-row',
                'header_css_class' => 'no-display',
            ));
        }

        return $grid;
    }

    public function addReorderColumn($grid)
    {
        $grid->addColumn('reorder', array(
            'header' => 'Reorder',
            'filter' => false,
            'sortable' => false,
            'width' => '100px',
            'renderer' => 'adminhtml/sales_reorder_renderer_action'
        ));

        return $grid;
    }

    protected function prepareGridColumns()
    {
        $helper = Mage::helper('iwd_ordermanager');

        $coreResource = Mage::getSingleton('core/resource');
        $tableNameSalesFlatOrder = $coreResource->getTableName('sales_flat_order');
        $tableNameSalesFlatInvoice = $coreResource->getTableName('sales_flat_invoice');
        $tableNameSalesFlatCreditmemo = $coreResource->getTableName('sales_flat_creditmemo');
        $tableNameSalesFlatShipment = $coreResource->getTableName('sales_flat_shipment');
        $tableNameSalesFlatShipmentTrack = $coreResource->getTableName('sales_flat_shipment_track');

        $columns = array(
            /*** main table ***/
            'increment_id' => array(
                'header' => $helper->__('Order #'),
                'index' => 'increment_id',
                'type' => 'text',
                'width' => '80px',
                'filter_index' => 'main_table.increment_id',
            ),
            'store_id' => array(
                'header' => $helper->__('Purchased From (Store)'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
                'filter_index' => 'main_table.store_id',
            ),
            'status' => array(
                'header' => $helper->__('Status'),
                'index' => 'status',
                'type' => 'options',
                'width' => '70px',
                'filter_index' => 'main_table.status',
                'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
            ),
            'base_grand_total' => array(
                'header' => $helper->__('G.T. (Base)'),
                'index' => 'base_grand_total',
                'type' => 'currency',
                'filter_index' => 'main_table.base_grand_total',
                'currency' => 'base_currency_code',
            ),
            'grand_total' => array(
                'header' => $helper->__('G.T. (Purchased)'),
                'index' => 'grand_total',
                'type' => 'currency',
                'filter_index' => 'main_table.grand_total',
                'currency' => 'order_currency_code',
            ),
            'created_at' => array(
                'header' => $helper->__('Purchased On'),
                'type' => 'datetime',
                'width' => '100px',
                'index' => 'created_at',
                'filter_index' => 'main_table.created_at',
            ),
            'updated_at' => array(
                'header' => $helper->__('Update At'),
                'type' => 'datetime',
                'width' => '100px',
                'index' => 'updated_at',
                'filter_index' => 'main_table.updated_at',
            ),
            'total_paid' => array(
                'header' => $helper->__('Total Paid'),
                'index' => 'total_paid',
                'type' => 'currency',
                'filter_index' => 'main_table.total_paid',
                'currency' => 'order_currency_code',
            ),
            'billing_name' => array(
                'header' => $helper->__('Bill to Name'),
                'index' => 'billing_name',
                'column_css_class' => 'nowrap',
            ),
            'shipping_name' => array(
                'header' => $helper->__('Ship to Name'),
                'type' => 'text',
                'index' => 'shipping_name',
                'filter_index' => 'main_table.shipping_name',
                'column_css_class' => 'nowrap',
            ),

            'base_shipping_amount' => array(
                'header' => $helper->__('Ship. Amount (Base)'),
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'index' => 'base_shipping_amount',
                'filter_index' => 'sales_flat_order.base_shipping_amount',
                'column_css_class' => 'nowrap',
            ),
            'shipping_amount' => array(
                'header' => $helper->__('Ship. Amount'),
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'index' => 'shipping_amount',
                'filter_index' => 'sales_flat_order.shipping_amount',
                'column_css_class' => 'nowrap',
            ),

            'base_total_invoiced' => array(
                'header' => $helper->__('Invoiced Amount (Base)'),
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'index' => 'base_total_invoiced',
                'filter_index' => 'sales_flat_order.base_total_invoiced',
                'column_css_class' => 'nowrap',
            ),
            'total_invoiced' => array(
                'header' => $helper->__('Invoiced Amount'),
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'index' => 'total_invoiced',
                'filter_index' => 'sales_flat_order.total_invoiced',
                'column_css_class' => 'nowrap',
            ),

            'base_total_refunded' => array(
                'header' => $helper->__('Refunded Amount (Base)'),
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'index' => 'base_total_refunded',
                'filter_index' => 'sales_flat_order.base_total_refunded',
                'column_css_class' => 'nowrap',
            ),
            'total_refunded' => array(
                'header' => $helper->__('Refunded Amount'),
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'index' => 'total_refunded',
                'filter_index' => 'sales_flat_order.total_refunded',
                'column_css_class' => 'nowrap',
            ),

            'base_discount_amount' => array(
                'header' => $helper->__('Discount Amount (Base)'),
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'index' => 'base_discount_amount',
                'filter_index' => 'sales_flat_order.base_discount_amount',
                'column_css_class' => 'nowrap',
            ),
            'discount_amount' => array(
                'header' => $helper->__('Discount Amount'),
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'index' => 'discount_amount',
                'filter_index' => 'sales_flat_order.discount_amount',
                'column_css_class' => 'nowrap',
            ),

            'subtotal' => array(
                'header' => $helper->__('Subtotal Amount (Base)'),
                'type' => 'currency',
                'currency' => 'base_currency_code',
                'index' => 'subtotal',
                'filter_index' => 'sales_flat_order.subtotal',
                'column_css_class' => 'nowrap',
            ),
            'base_subtotal' => array(
                'header' => $helper->__('Subtotal Amount'),
                'type' => 'currency',
                'currency' => 'order_currency_code',
                'index' => 'base_subtotal',
                'filter_index' => 'sales_flat_order.base_subtotal',
                'column_css_class' => 'nowrap',
            ),

            /*** order items ***/
            'ordered_products' => array(
                'header' => Mage::helper('catalog')->__('Item(s) Ordered'),
                'index' => 'ordered_products',
                'filter_index' => 'order_items.name',
                'type' => 'text',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Items(),
            ),
            'product_sku' => array(
                'header' => $helper->__('SKU(s)'),
                'index' => 'product_sku',
                'filter_index' => 'sku',
                'type' => 'text',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Sku(),
            ),

            'product_images' => array(
                'header' => $helper->__('Product Images'),
                'index' => 'product_images',
                'filter' => false,
                'sortable' => false,
                'type' => 'text',
                'width' => '170px',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Images(),
            ),

            'qty' => array(
                'header' => $helper->__('Quantity'),
                'index' => 'qty',
                'type' => 'text',
                'filter' => false,
                'sortable' => false,
                'width' => '150px',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Quantity()
            ),
            'tax_amount' => array(
                'header' => $helper->__('Tax Amount'),
                'index' => 'tax_amount',
                'type' => 'currency',
                'filter_index' => 'sales_flat_order.tax_amount',
                'currency' => 'order_currency_code',
            ),

            'base_tax_amount' => array(
                'header' => $helper->__('Tax Amount (Base)'),
                'index' => 'base_tax_amount',
                'type' => 'currency',
                'filter_index' => 'sales_flat_order.base_tax_amount',
                'currency' => 'base_currency_code',
            ),

            /*** sales order objects ***/
            'creditmemo' => array(
                'header' => $helper->__('Credit Memo(s)'),
                'index' => 'creditmemo',
                'type' => 'text',
                'filter_index' => "{$tableNameSalesFlatCreditmemo}.increment_id",
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Creditmemo(),
            ),
            'invoice' => array(
                'header' => $helper->__('Invoice(s)'),
                'index' => 'invoice',
                'type' => 'text',
                'filter_index' => "{$tableNameSalesFlatInvoice}.increment_id",
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Invoice(),
            ),
            'shipment' => array(
                'header' => $helper->__('Shipment(s)'),
                'index' => 'shipment',
                'type' => 'text',
                'filter_index' => "{$tableNameSalesFlatShipment}.increment_id",
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Shipment(),
            ),

            'track_number' => array(
                'header' => $helper->__('Track Number(s)'),
                'index' => 'track_number',
                'type' => 'text',
                'filter_index' => "{$tableNameSalesFlatShipmentTrack}.track_number",
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Tracknumber(),
            ),

            'weight' => array(
                'header' => $helper->__('Weight'),
                'type' => 'number',
                'index' => 'weight',
                'filter_index' => "{$tableNameSalesFlatOrder}.weight",
            ),

            'payment_method' => array(
                'header' => $helper->__('Payment Method'),
                'index' => 'payment_method',
                'type' => 'options',
                'width' => '70px',
                'filter_index' => 'method',
                'column_css_class' => 'nowrap',
                'options' => Mage::getModel('iwd_ordermanager/payment_payment')->GetPaymentMethods(),
            ),

            'shipping_description' => array(
                'type' => 'text',
                'header' => $helper->__('Shipping Method'),
                'index' => 'shipping_description',
                'filter_index' => 'shipping_description',
                'column_css_class' => 'nowrap',
                'align' => 'center'
            ),

            'customer_email' => array(
                'type' => 'text',
                'header' => $helper->__('Customer Email'),
                'index' => 'customer_email'
            ),

            'coupon_code' => array(
                'type' => 'text',
                'header' => $helper->__('Coupon Code'),
                'align' => 'center',
                'index' => 'coupon_code'
            ),

            /***** BILLING ADDRESS *****/
            'billing_address' => array(
                'header' => $helper->__('Billing Address'),
                'index' => 'billing_address',
                'type' => 'text',
                'filter_index' => 'bill.postcode',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Billing(),
                'filter_condition_callback' => array($this, '_billingAddressFilter'),
            ),
            'billing_company' => array(
                'header' => $helper->__('Bill Company'),
                'index' => 'billing_company',
                'filter_index' => 'bill.company',
                'column_css_class' => 'nowrap',
            ),

            'billing_street' => array(
                'header' => $helper->__('Bill Street'),
                'index' => 'billing_street',
                'filter_index' => 'bill.street',
                'column_css_class' => 'nowrap',
            ),

            'billing_postcode' => array(
                'header' => $helper->__('Bill Postcode'),
                'index' => 'billing_postcode',
                'filter_index' => 'bill.postcode',
            ),

            'billing_state' => array(
                'header' => $helper->__('Bill Region'),
                'index' => 'billing_region',
                'filter_index' => 'bill.region',
                'column_css_class' => 'nowrap',
            ),

            'billing_country' => array(
                'header' => $helper->__('Bill Country'),
                'index' => 'billing_country_id',
                'type' => 'country',
                'filter_index' => 'bill.country_id',
                'column_css_class' => 'nowrap',
            ),

            'billing_city' => array(
                'header' => $helper->__('Bill City'),
                'index' => 'billing_city',
                'filter_index' => 'bill.city',
                'column_css_class' => 'nowrap',
            ),

            'billing_telephone' => array(
                'header' => $helper->__('Bill Phone'),
                'index' => 'billing_telephone',
                'filter_index' => 'bill.telephone',
            ),
            /***** -end- billing address *****/

            /****** SHIPPING ADDRESS ******/
            'shipping_address' => array(
                'header' => $helper->__('Shipping Address'),
                'index' => 'shipping_address',
                'type' => 'text',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Shipping(),
                'filter_index' => 'ship.postcode',
                'filter_condition_callback' => array($this, '_shippingAddressFilter'),
            ),

            'shipping_company' => array(
                'header' => $helper->__('Ship Company'),
                'index' => 'shipping_company',
                'filter_index' => 'ship.company',
                'column_css_class' => 'nowrap',
            ),

            'shipping_street' => array(
                'header' => $helper->__('Ship Street'),
                'index' => 'shipping_street',
                'filter_index' => 'ship.street',
                'column_css_class' => 'nowrap',
            ),

            'shipping_postcode' => array(
                'header' => $helper->__('Ship Postcode'),
                'index' => 'shipping_postcode',
                'filter_index' => 'ship.postcode',
            ),

            'shipping_state' => array(
                'header' => $helper->__('Ship Region'),
                'index' => 'shipping_region',
                'filter_index' => 'ship.region',
                'column_css_class' => 'nowrap',
            ),

            'shipping_country' => array(
                'type' => 'country',
                'header' => $helper->__('Ship Country'),
                'index' => 'shipping_country_id',
                'filter_index' => 'ship.country_id',
                'column_css_class' => 'nowrap',
            ),

            'shipping_telephone' => array(
                'header' => $helper->__('Ship Phone'),
                'index' => 'shipping_telephone',
                'filter_index' => 'ship.telephone',
            ),

            'shipping_city' => array(
                'header' => $helper->__('Ship City'),
                'index' => 'shipping_city',
                'filter_index' => 'ship.city',
                'column_css_class' => 'nowrap',
            ),
            /***** -end- shipping address *****/

            'customer_note' => array(
                'header' => $helper->__('Customer Note'),
                'index' => 'customer_note',
                'type' => 'text',
                'width' => '300px',
                'filter_index' => 'customer_note',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Removetags(),
            ),

            'order_comment' => array(
                'header' => $helper->__('Last Order Comment'),
                'index' => 'order_comment',
                'filter_index' => 'ordercomment_table.comment',
                'width' => '300px',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Lastcomment(),
            ),

            'order_comment_first' => array(
                'header' => $helper->__('First Order Comment'),
                'filter' => false,
                'sortable' => false,
                'width' => '300px',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Firstcomment(),
            ),
        );

        if (Mage::helper('iwd_ordermanager')->isMultiInventoryEnable()) {
            $columns['inventory'] = array(
                'header' => $helper->__('Assign Stock'),
                'type' => 'options',
                'index' => 'stock_assigned',
                'filter_index' => 'iwd_cataloginventory_stock_order.assigned',
                'width' => '100px',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Inventory(),
                'options' => IWD_OrderManager_Model_Cataloginventory_Stock_Order_Status::getStatuses(),
            );
        }

        $flagTypes = Mage::getModel('iwd_ordermanager/flags_types')->getCollection();
        foreach ($flagTypes as $type) {
            $orderGrid = $type->getOrderGridId();

            $columns[$orderGrid] = array(
                'header' => $type->getName(),
                'type' => 'options',
                'index' => $orderGrid,
                'filter_index' => $orderGrid . '.flag_id',
                'width' => '100px',
                'column_css_class' => 'v-align',
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Flags(),
                'options' => $type->getAssignedFlags(),
            );
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $columns['action'] = array(
                'header' => $helper->__('Actions'),
                'width' => '60px',
                'type' => 'text',
                'getter' => 'getId',
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
                'renderer' => new IWD_OrderManager_Block_Adminhtml_Sales_Order_Grid_Renderer_Actions(),
            );
        }

        $columns['archived'] = array(
            'header' => $helper->__('Archived'),
            'index' => 'archived',
            'filter_index' => 'archived',
            'type' => 'options',
            'width' => '70px',
            'options' => array(0 => $helper->__('Actual'), 1 => $helper->__('Archived')),
            'filter_condition_callback' => array($this, '_archivedFilter'),
        );

        return $columns;
    }

    protected function _billingAddressFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $collection->getSelect()->where(
            "bill.city like ?
             OR bill.street like ?
             OR bill.region like ?
             OR bill.postcode like ?
             OR bill.fax like ?
             OR bill.telephone like ?"
            , "%$value%");

        return $this;
    }

    protected function _shippingAddressFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $collection->getSelect()->where(
            "ship.city like ?
             OR ship.street like ?
             OR ship.region like ?
             OR ship.postcode like ?
             OR ship.fax like ?
             OR ship.telephone like ?"
            , "%$value%");

        return $this;
    }

    protected function _archivedFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            $collection->getSelect()->where("sales_flat_order_grid.entity_id IS NOT NULL");
        } else {
            $collection->getSelect()->where("sales_flat_order_grid.entity_id IS NULL");
        }

        return $this;
    }
}
