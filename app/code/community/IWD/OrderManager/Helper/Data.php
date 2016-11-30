<?php

class IWD_OrderManager_Helper_Data extends Mage_Core_Helper_Data
{
    const CONFIG_XML_PATH_SHOW_ITEM_IMAGE = 'iwd_ordermanager/edit/show_item_image';
    const CONFIG_XML_CUSTOM_GRID_ENABLE = 'iwd_ordermanager/grid_order/enable';
    const CONFIG_XML_CONFIRM_EDIT_CHECKED = 'iwd_ordermanager/edit/confirm_edit_checked';
    const CONFIG_XML_NOTIFY_CUSTOMER_CHECKED = 'iwd_ordermanager/edit/notify_checked';
    const CONFIG_XML_PATH_RECALCULATE_ORDER_AMOUNT = 'iwd_ordermanager/edit/recalculate_amount_checked';
    const CONFIG_XML_PATH_VALIDATE_INVENTORY = 'iwd_ordermanager/edit/validate_inventory';

    public function isGridExport()
    {
        $path = "";
        if (isset($_SERVER['PATH_INFO'])) {
            $path = $_SERVER['PATH_INFO'];
        } else if (isset($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
        }
        $exportCsv = (strstr($path, 'exportCsv') !== false);
        $exportExcel = (strstr($path, 'exportExcel') !== false);
        return $exportCsv || $exportExcel;
    }


    public function isRecalculateOrderAmountChecked()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_RECALCULATE_ORDER_AMOUNT, Mage::app()->getStore()) ? 'checked="checked"' : "";
    }

    public function isValidateInventory()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_VALIDATE_INVENTORY, Mage::app()->getStore()) ? 1 : 0;
    }

    public function getExtensionVersion()
    {
        return Mage::getConfig()->getModuleConfig("IWD_OrderManager")->version;
    }

    public function CheckTableEngine($table)
    {
        $cache = Mage::app()->getCache();

        $engine = $cache->load("iwd_order_manager_engine");
        if ($engine !== false) {
            return (bool)$engine;
        }

        try {
            $dbname = (string)Mage::getConfig()->getResourceConnectionConfig('default_setup')->dbname;
            $sql = "SELECT engine FROM `information_schema`.`tables` WHERE `table_schema`='{$dbname}' AND `table_name`='{$table}'";
            $data = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

            $isEngineInno = ($data[0]["engine"] == "InnoDB") ? 'true' : 'false';
            $cache->save("{$isEngineInno}", 'iwd_order_manager_engine', array("iwd_order_manager_engine"), 3600);
            return $isEngineInno;
        } catch (Exception $e) {
            IWD_OrderManager_Model_Logger::log($e->getMessage());
        }

        return false;
    }

    public function isShowItemImage()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_PATH_SHOW_ITEM_IMAGE, Mage::app()->getStore());
    }

    public function isConfirmEditChecked()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_CONFIRM_EDIT_CHECKED) ? 'checked="checked"' : "";
    }

    public function isNotifyCustomerCheckedDefault()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_NOTIFY_CUSTOMER_CHECKED);
    }

    public function isNotifyCustomerChecked()
    {
        return $this->isNotifyCustomerCheckedDefault() ? 'checked="checked"' : "";
    }

    public function enableCustomGrid()
    {
        return Mage::getStoreConfig(self::CONFIG_XML_CUSTOM_GRID_ENABLE);
    }

    public function CheckOrderTableEngine()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
        return $this->CheckTableEngine($table);
    }

    public function CheckCreditmemoTableEngine()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('sales_flat_creditmemo');
        return $this->CheckTableEngine($table);
    }

    public function CheckInvoiceTableEngine()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('sales_flat_invoice');
        return $this->CheckTableEngine($table);
    }

    public function CheckShipmentTableEngine()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('sales_flat_shipment');
        return $this->CheckTableEngine($table);
    }

    protected $_version = 'CE';

    public function isEnterpriseMagentoEdition()
    {
        return ($this->getEdition() == 'Enterprise');
    }

    public function isAvailableVersion()
    {
        if ($this->isEnterpriseMagentoEdition() && $this->_version == 'CE') {
            return false;
        }

        return true;
    }

    public function getEdition()
    {
        $mage = new Mage();
        if (!is_callable(array($mage, 'getEdition'))) {
            $edition = 'Community';
        } else {
            $edition = Mage::getEdition();
        }

        unset($mage);

        return $edition;
    }

    public function getCurrentIpAddress()
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }

    public function getDataTimeFormat()
    {
        return 'm-d-Y H:i:s';
    }

    public function getDateTime($date)
    {
        $storeId = null;
        $timezone = Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE, $storeId);
        $locale = new Zend_Locale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $storeId));
        $date = new Zend_Date(strtotime($date), null, $locale);
        $date->setTimezone($timezone);
        return $date->get('MM-dd-Y H:m:s');
    }

    public function isCustomCreationProcess()
    {
        return Mage::getStoreConfig('iwd_ordermanager/crate_process/enable');
    }

    public function isMultiInventoryEnable()
    {
        return Mage::getStoreConfig('iwd_ordermanager/multi_inventory/enable');
    }

    public function isAutoReAuthorization()
    {
        return (bool)(int)Mage::getStoreConfig('iwd_ordermanager/edit/deferred_re_authorization');
    }

    public function checkApiCredentials()
    {
        $standard_auth = Mage::getStoreConfig('iwd_settlementreport/connection/standard');
        if($standard_auth){
            $active = Mage::getStoreConfig('payment/authorizenet/active');

            if(!$active){
                $message = $this->__('Authorize.net payment method is disabled.');
                return array('error'=>1, 'message'=>$message);
            }
        } else {
            $login = Mage::getStoreConfig('iwd_settlementreport/connection/login');
            $trans_key = Mage::getStoreConfig('iwd_settlementreport/connection/trans_key');

            if(!$login || !$trans_key){
                $message = $this->__('Enter API credentials and save to test.');
                return array('error'=>1, 'message'=>$message);
            }
        }

        try {
            $date = substr(date('c',time()),0,-6);
            $details = Mage::getModel('iwd_ordermanager/authorize_authorizeNet')->getSettledBatchList(false, $date, $date);
            $result = (array)$details->xml->messages;

            if(!isset($result['resultCode']) || $result['resultCode'] == 'Error'){
                $message = (array)$result['message'];
                return array('error'=>1, 'message'=>$message["code"] . ": " . $message["text"]);
            }
        } catch (Exception $e) {
            return array('error'=>1, 'message'=>$this->__($e->getMessage()));
        }

        return array('error'=>0, 'message'=>$this->__('Connected successfully.'));
    }

    /**
     * @param $exclPrice
     * @param $inclPrice
     * @return float|int
     */
    public function getRoundPercent($exclPrice, $inclPrice)
    {
        $percent = ($exclPrice != 0) ? ($inclPrice / $exclPrice - 1) * 100 : 0;

        $rates = Mage::getModel('tax/calculation_rate')->getCollection()
            ->addFieldToSelect('rate')
            ->getColumnValues('rate');

        for ($i = 5; $i >= 0; $i--) {
            $roundedPercent = round($percent, $i);
            if (in_array($roundedPercent, $rates)) {
                return $roundedPercent;
            }
        }

        return round($percent, 2);
    }
}
