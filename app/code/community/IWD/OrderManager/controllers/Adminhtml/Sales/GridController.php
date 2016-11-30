<?php

class IWD_OrderManager_Adminhtml_Sales_GridController extends Mage_Adminhtml_Controller_Action
{
    public function deleteAction()
    {
        $redirect = $this->getRequest()->getParam('redirect');
        $redirect = (empty($redirect)) ? "*/sales_order/index" : "*/{$redirect}/index";

        if (Mage::getModel('iwd_ordermanager/order')->isAllowDeleteOrders()) {
            try {
                $checkedOrders = $this->getCheckedOrderIds();
                foreach ($checkedOrders as $orderId) {
                    $order = Mage::getModel('iwd_ordermanager/order')->load($orderId);
                    if ($order->getEntityId()) {
                        if ($order->deleteOrder()) {
                            $this->deleteFromOrderGrid($orderId);
                        }
                    } else {
                        $this->deleteFromOrderGrid($orderId);
                    }
                }

                Mage::getSingleton('iwd_ordermanager/report')->AggregateSales();
                Mage::getSingleton('iwd_ordermanager/logger')->addMessageToPage();
            } catch (Exception $e) {
                IWD_OrderManager_Model_Logger::log($e->getMessage());
                $this->_getSession()->addError($this->__('An error during the deletion. %s', $e->getMessage()));
                $this->_redirect($redirect);
                return;
            }
        } else {
            $this->_getSession()->addError($this->__('This feature was deactivated.'));
            $this->_redirect($redirect);
            return;
        }

        $this->_redirect($redirect);
    }

    public function deleteFromOrderGrid($orderId)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        /* from order grid table */
        try {
            $salesFlatOrderGrid = Mage::getSingleton('core/resource')->getTableName('sales_flat_order_grid');
            $connection->beginTransaction();
            $connection->delete($salesFlatOrderGrid, array($connection->quoteInto('entity_id=?', $orderId)));
            $connection->commit();
        } catch (Exception $e) {
            IWD_OrderManager_Model_Logger::log($e->getMessage());
        }

        /* from archive order grid table */
        if (!Mage::helper('iwd_ordermanager')->isEnterpriseMagentoEdition()) {
            try {
                $iwdSalesArchiveOrderGrid = Mage::getSingleton('core/resource')->getTableName('iwd_sales_archive_order_grid');
                $connection->beginTransaction();
                $connection->delete($iwdSalesArchiveOrderGrid, array($connection->quoteInto('entity_id=?', $orderId)));
                $connection->commit();
            } catch (Exception $e) {
                IWD_OrderManager_Model_Logger::log($e->getMessage());
            }
        }
    }

    public function changeStatusAction()
    {
        $redirect = "*/sales_order/index";

        if (Mage::getModel('iwd_ordermanager/order')->isAllowChangeOrderStatus()) {
            try {
                $status_id = $this->getRequest()->getParam('status');
                $checkedOrders = $this->getCheckedOrderIds();

                foreach ($checkedOrders as $orderId) {
                    $order = Mage::getModel('iwd_ordermanager/order')->load($orderId);
                    if ($order->getId()) {
                        $logger = Mage::getSingleton('iwd_ordermanager/logger');
                        $old_status_id = $order->getStatus();
                        $old_status = Mage::getResourceModel('sales/order_status_collection')->addStateFilter($old_status_id)->getData();
                        $logger->addChangesToLog('order_status', $old_status[0]['label'], $status_id);
                        $logger->addCommentToOrderHistoryInGrid($orderId, $status_id, false);
                        $logger->addLogToLogTable(IWD_OrderManager_Model_Confirm_Options_Type::ORDER_INFO, $orderId);
                        $order->setData('status', $status_id)->save();
                    }
                }
                $this->_getSession()->addSuccess($this->__('Status was successfully changed'));
            } catch (Exception $e) {
                IWD_OrderManager_Model_Logger::log($e->getMessage());
                $this->_getSession()->addError($this->__('An error arose during the updating. %s', $e));
            }
        } else {
            $this->_getSession()->addError($this->__('This feature was deactivated.'));
        }
        $this->_redirect($redirect);
    }

    public function orderedItemsAction()
    {
        $result = array('status' => 1);

        try {
            $orderId = $this->getRequest()->getPost('order_id');
            $ordered = Mage::getModel('sales/order')->load($orderId)->getItemsCollection();

            $result['table'] = $this->getLayout()
                ->createBlock('iwd_ordermanager/adminhtml_sales_order_grid_ordereditems')
                ->setData('ordered', $ordered)
                ->setData('order_id', $orderId)
                ->toHtml();
        } catch (Exception $e) {
            IWD_OrderManager_Model_Logger::log($e->getMessage());
            $result = array('status' => 0, 'error' => $e->getMessage());
        }

        $this->prepareResponse($result);
    }

    public function productItemsAction()
    {
        $result = array('status' => 1);

        try {
            $orderId = $this->getRequest()->getPost('order_id');
            $ordered = Mage::getModel('sales/order')->load($orderId)->getItemsCollection();

            $products = array();
            foreach ($ordered as $item) {
                $productId = $item->getProductId();
                $products[$productId] = Mage::getModel('catalog/product')->load($productId);
            }

            $result['table'] = $this->getLayout()
                ->createBlock('iwd_ordermanager/adminhtml_sales_order_grid_productitems')
                ->setData('products', $products)
                ->setData('order_id', $orderId)
                ->toHtml();
        } catch (Exception $e) {
            IWD_OrderManager_Model_Logger::log($e->getMessage());
            $result = array('status' => 0, 'error' => $e->getMessage());
        }

        $this->prepareResponse($result);
    }

    protected function getCheckedOrderIds()
    {
        $checkedOrders = $this->getRequest()->getParam('order_ids');
        if (!is_array($checkedOrders)) {
            $checkedOrders = array($checkedOrders);
        }
        return $checkedOrders;
    }

    protected function _isAllowed()
    {
        $action = $this->getRequest()->getActionName();
        $action = strtolower($action);
        if ($action == 'delete') {
            return Mage::getSingleton('admin/session')->isAllowed('iwd_ordermanager/order/actions/delete');
        }

        return true;
    }

    protected function prepareResponse($result)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json', true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}