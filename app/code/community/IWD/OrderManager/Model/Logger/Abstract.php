<?php

class IWD_OrderManager_Model_Logger_Abstract extends Mage_Core_Model_Abstract
{
    protected $delete_log_success = array();
    protected $delete_log_error = array();
    protected $notices = array();
    protected $changes_log = array();
    protected $order_address_log = array();
    protected $edited_order_items = array();
    protected $added_order_items = array();
    protected $ordered_items_name = array();
    protected $remove_order_items = array();

    protected $new_totals = array();
    protected $log_output = "";
    protected $log_notices = "";

    const BR = "&nbsp;<br/>";

    protected $order_params = array(
        'order_status' => "Changed status from '%s' to '%s'",
        'order_state' => "Changed state from '%s' to '%s'",
        'order_store_name' => "Changed purchased from store '%s' to '%s'",
        'created_at' => "Changed order date from '%s' to '%s'",
        'order_increment_id' => "Changed order number from '%s' to '%s'",

        'payment_method' => "Payment method was changed from '%s' to '%s'",
        'shipping_method' => "Shipping method was changed from '%s' to '%s'",
        'shipping_amount' => "Shipping amount was changed from '%s' to '%s'",

        'customer_group_id' => "Order customer group was changed from '%s' to '%s'",
        'customer_prefix' => "Order customer prefix was changed from '%s' to '%s'",
        'customer_firstname' => "Order customer first name was changed from '%s' to '%s'",
        'customer_middlename' => "Order customer middle name was changed from '%s' to '%s'",
        'customer_lastname' => "Order customer last name was changed from '%s' to '%s'",
        'customer_suffix' => "Order customer suffix was changed from '%s' to '%s'",
        'customer_email' => "Order customer e-mail was changed from '%s' to '%s'",
    );

    /**
     * add to log
     *
     * @param $orderItem
     * @param $description
     * @param $old
     * @param null $new
     */
    public function addOrderItemEdit($orderItem, $description, $old, $new = null)
    {
        $description = Mage::helper('iwd_ordermanager')->__($description);
        $this->ordered_items_name[$orderItem->getId()] = $orderItem->getName();

        if ($new === null) {
            $this->edited_order_items[$orderItem->getId()][] = sprintf(' - %s: "%s"', $description, $old) . self::BR;
            return;
        }

        if ($old != $new) {
            $this->edited_order_items[$orderItem->getId()][] = sprintf(' - %s: "%s" to "%s"', $description, $old, $new) . self::BR;
        }
    }

    /**
     * @param $orderItem
     */
    public function addOrderItemAdd($orderItem)
    {
        $this->added_order_items[$orderItem->getId()] = $orderItem->getName();
        $this->ordered_items_name[$orderItem->getId()] = $orderItem->getName();
    }

    /**
     * @param $orderItem
     * @param bool|false $refund
     */
    public function addOrderItemRemove($orderItem, $refund = false)
    {
        $this->remove_order_items[$orderItem->getId()] = $refund;
        $this->ordered_items_name[$orderItem->getId()] = $orderItem->getName();
    }

    /**
     * @param $item
     * @param $oldValue
     * @param $newValue
     */
    public function addChangesToLog($item, $oldValue, $newValue)
    {
        if ($newValue != $oldValue) {
            $this->changes_log[$item] = array(
                "new" => $newValue,
                "old" => $oldValue,
            );
        }
    }

    /**
     * @param $addressType
     * @param $filed
     * @param $title
     * @param $oldValue
     * @param $newValue
     */
    public function addAddressFieldChangesToLog($addressType, $filed, $title, $oldValue, $newValue)
    {
        if ($newValue != $oldValue) {
            if ($filed == "region_id") {
                $filed = "region";
                $newValue = Mage::getModel('directory/region')->load($newValue)->getName();
                $oldValue = Mage::getModel('directory/region')->load($oldValue)->getName();

                if (isset($this->order_address_log[$addressType][$filed]['new']) && !empty($this->order_address_log[$addressType][$filed]['new'])) {
                    $newValue = $this->order_address_log[$addressType][$filed]['new'];
                }
                if (isset($this->order_address_log[$addressType][$filed]['old']) && !empty($this->order_address_log[$addressType][$filed]['old'])) {
                    $oldValue = $this->order_address_log[$addressType][$filed]['old'];
                }
            }

            if ($filed == "country_id") {
                $filed = "country";
                $newValue = Mage::getModel('directory/country')->loadByCode($newValue)->getName();
                $oldValue = Mage::getModel('directory/country')->loadByCode($oldValue)->getName();
            }

            $this->order_address_log[$addressType][$filed] = array(
                "new" => $newValue,
                "old" => $oldValue,
                "title" => $title
            );
        }
    }

    /**
     * @param $item
     * @param $itemIncrementId
     */
    public function itemDeleteSuccess($item, $itemIncrementId)
    {
        $this->delete_log_success[$item][] = $itemIncrementId;
    }

    /**
     * @param $item
     * @param $itemIncrementId
     */
    public function itemDeleteError($item, $itemIncrementId)
    {
        $this->delete_log_error[$item][] = $itemIncrementId;
    }

    /**
     * @param $noticeId
     * @param $message
     */
    public function addNoticeMessage($noticeId, $message)
    {
        $this->notices[$noticeId] = $message;
    }

    /**
     * @param $item
     */
    protected function addInfoAboutSuccessAddedItemsToMessage($item)
    {
        $count = isset($this->delete_log_success[$item]) ? count($this->delete_log_success[$item]) : 0;

        if ($count > 0) {
            if ($count == 1) {
                $message = Mage::helper('iwd_ordermanager')->__("The sale %s #%s has been deleted successfully.");
                $itemTitle = Mage::helper('iwd_ordermanager')->__($item);
                $message = sprintf($message, $itemTitle, $this->delete_log_success[$item][0]);
            } else {
                $message = Mage::helper('iwd_ordermanager')->__("%i %s have been deleted successfully: %s");
                $ids = '#' . implode(', #', $this->delete_log_success[$item]);
                $itemTitle = Mage::helper('iwd_ordermanager')->__($item);
                $message = sprintf($message, $count, $itemTitle, $ids);
            }
            Mage::getSingleton('adminhtml/session')->addSuccess($message);
        }
    }

    /**
     * @param $item
     */
    protected function addInfoAboutErrorAddedItemsToMessage($item)
    {
        $count = isset($this->delete_log_error[$item]) ? count($this->delete_log_error[$item]) : 0;

        if ($count > 0) {
            if ($count == 1) {
                $message = Mage::helper('iwd_ordermanager')->__("The sale %s #%s can not be deleted.");
                $itemTitle = Mage::helper('iwd_ordermanager')->__($item);
                $message = sprintf($message, $itemTitle, $this->delete_log_error[$item][0]);
            } else {
                $message = Mage::helper('iwd_ordermanager')->__("%i %s can not be deleted: %s");
                $ids = '#' . implode(', #', $this->delete_log_error[$item]);
                $itemTitle = Mage::helper('iwd_ordermanager')->__($item);
                $message = sprintf($message, $count, $itemTitle, $ids);
            }
            Mage::getSingleton('adminhtml/session')->addError($message);
        }
    }

    /**
     * output
     */
    public function addMessageToPage()
    {
        $items = array('order', 'invoice', 'shipment', 'creditmemo');
        foreach ($items as $item) {
            $this->addInfoAboutSuccessAddedItemsToMessage($item);
            $this->addInfoAboutErrorAddedItemsToMessage($item);
        }

        foreach ($this->notices as $notice) {
            Mage::getSingleton('adminhtml/session')->addNotice($notice);
        }
    }

    /**
     * @param null $orderId
     * @return null
     */
    public function getLogOutput($orderId = null)
    {
        if (empty($this->log_output)) {
            $this->log_output = "";
            $this->addToLogOutputInfoAboutOrderChanges();
            $this->addToLogOutputInfoAboutOrderAddress();
            $this->addToLogOutputInfoAboutOrderItems();
            $this->addtoLogOutputInfoAboutOrderTotals($orderId);
            $this->addToLogOutputNotices();
        }

        return null;
    }

    /**
     * @return void
     */
    protected function addToLogOutputInfoAboutOrderChanges()
    {
        $helper = Mage::helper('iwd_ordermanager');
        foreach ($this->order_params as $itemCode => $itemMessage) {
            if (isset($this->changes_log[$itemCode])) {
                $this->log_output .= sprintf($helper->__($itemMessage), $this->changes_log[$itemCode]['old'], $this->changes_log[$itemCode]['new']) . self::BR;
            }
        }
    }

    /**
     * @return void
     */
    protected function addToLogOutputInfoAboutOrderAddress()
    {
        $helper = Mage::helper('iwd_ordermanager');

        foreach (array("billing", "shipping") as $addressType) {
            if (isset($this->order_address_log[$addressType]) && !empty($this->order_address_log[$addressType])) {
                $this->log_output .= $helper->__("Order {$addressType} address updated: ") . self::BR;
                foreach ($this->order_address_log[$addressType] as $id => $field) {
                    $this->log_output .= sprintf(' - %s from "%s" to "%s"', $field['title'], $field['old'], $field['new']) . self::BR;
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function addToLogOutputInfoAboutOrderItems()
    {
        $helper = Mage::helper('iwd_ordermanager');

        /*** add order items ***/
        if (!empty($this->added_order_items)) {
            foreach ($this->added_order_items as $itemId => $item_name) {
                $this->log_output .= "<b>{$item_name}</b> {$helper->__('was added')}" . self::BR;
            }
        }

        /*** edit order items ***/
        if (!empty($this->edited_order_items)) {
            foreach ($this->edited_order_items as $itemId => $_edited) {
                $this->log_output .= '<b>' . $this->ordered_items_name[$itemId] . '</b> ' . $helper->__('was edited') . ':' . self::BR;
                foreach ($_edited as $e) {
                    $this->log_output .= $e;
                }
            }
        }

        /*** remove order items ***/
        if (!empty($this->remove_order_items)) {
            foreach ($this->remove_order_items as $itemId => $refunded) {
                $message = ($refunded) ? $helper->__('was removed (refunded)') : $helper->__('was removed');
                $this->log_output .= "<b>{$this->ordered_items_name[$itemId]}</b> {$message}" . self::BR;
            }
        }
    }

    /**
     * @param $orderId
     * @return void
     */
    public function addtoLogOutputInfoAboutOrderTotals($orderId)
    {
        if (empty($orderId) || empty($this->new_totals)) {
            return;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        $helper = Mage::helper('iwd_ordermanager');

        $this->log_output .= self::BR .
            $helper->__('Old grand total: ') . Mage::helper('core')->currency($order->getBaseGrandTotal(), true, false) . self::BR .
            $helper->__('New grand total: ') . Mage::helper('core')->currency($this->new_totals['base_grand_total'], true, false) . self::BR .
            $helper->__('Changes: ') . Mage::helper('core')->currency($this->new_totals['base_grand_total'] - $order->getBaseGrandTotal(), true, false) . self::BR;
    }

    /**
     * @param $totals
     * @return void
     */
    public function addNewTotalsToLog($totals)
    {
        $this->new_totals = $totals;
    }

    /**
     * @param $message
     */
    public function addNoticeToLog($message)
    {
        $this->log_notices .= $message . self::BR;
    }

    /**
     * @return string
     */
    public function addToLogOutputNotices()
    {
        if (empty($this->log_notices)) {
            return $this->log_output;
        }

        return $this->log_output .= self::BR . $this->log_notices;
    }

    /**
     * @param $message
     * @return string
     */
    public function addToLog($message)
    {
        return $this->log_output .= $message;
    }
}
