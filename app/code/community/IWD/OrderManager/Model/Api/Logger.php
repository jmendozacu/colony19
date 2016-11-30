<?php

class IWD_OrderManager_Model_Api_Logger extends IWD_OrderManager_Model_Logger
{
    public function getConfirmLogger()
    {
        return Mage::getModel('iwd_ordermanager/confirm_api_logger');
    }
}