<?php

class IWD_OrderManager_Model_System_Config_Gridcolumn_Customergrid extends IWD_OrderManager_Model_System_Config_Gridcolumn_Order
{
    protected function getSelectedColumnsArray()
    {
        return Mage::getModel('iwd_ordermanager/order_grid')
            ->getSelectedColumnsArray(IWD_OrderManager_Model_Customer_Order::XML_PATH_CUSTOMER_ORDERS_ORDER_GRID_COLUMN);
    }
}
