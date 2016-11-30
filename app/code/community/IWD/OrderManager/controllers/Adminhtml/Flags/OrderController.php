<?php

class IWD_OrderManager_Adminhtml_Flags_OrderController extends IWD_OrderManager_Controller_Abstract
{
    public function massApplyFlagAction()
    {
        try {
            $orderIds = $this->getOrderIds();
            $flagId = $this->getFlagId();
            $typeId = $this->getFlagTypeId();
            foreach ($orderIds as $orderId) {
                Mage::getModel('iwd_ordermanager/flags_orders')->addNewRelation($flagId, $orderId, $typeId);
            }

            $this->_getSession()->addSuccess('Flag(s) was assigned to order(s)');
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/sales_order/');
    }

    /**
     * @return array
     */
    protected function getOrderIds()
    {
        return $this->getRequest()->getParam('order_ids', array());
    }


    public function applyFlagAction()
    {
        try {
            $flagId = $this->getFlagId();
            $orderId = $this->getOrderId();
            $typeId = $this->getFlagTypeId();

            Mage::getModel('iwd_ordermanager/flags_orders')->addNewRelation($flagId, $orderId, $typeId);
            $result = array('status' => 1, 'flagHtml' => $this->getFlagHtml());
        } catch(Exception $e) {
            IWD_OrderManager_Model_Logger::log($e->getMessage());
            $result = array('status' => 0, 'error' => $e->getMessage());
        }

        $this->prepareResponse($result);
    }

    protected function getFlagHtml()
    {
        $flagId = $this->getFlagId();
        $flag = Mage::getModel('iwd_ordermanager/flags_flags')->load($flagId);
        return $flag->getIconHtmlWithHint();
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function getFlagId()
    {
        $flag = $this->getRequest()->getParam('flag_id', 0);
        if (empty($flag)) {
            throw new Exception('Incorrect param flag');
        }

        return $flag;
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function getFlagTypeId()
    {
        $type = $this->getRequest()->getParam('type_id', 0);
        if (empty($type)) {
            throw new Exception('Incorrect param type');
        }

        return $type;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('iwd_ordermanager/order/actions/assign_flags');
    }
}
