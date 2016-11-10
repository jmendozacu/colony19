<?php
/**
 * Paybox Epayment module for Magento
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * available at : http://opensource.org/licenses/osl-3.0.php
 *
 * @package    Paybox_Epayment
 * @copyright  Copyright (c) 2013-2014 Paybox
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Paybox_Epayment_Block_Info extends Mage_Payment_Block_Info {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('pbxep/info/default.phtml');
    }

    public function getCreditCards() {
        $result = array();
        $cards = $this->getMethod()->getCards();
        $selected = explode(',', $this->getMethod()->getConfigData('cctypes'));
        foreach ($cards as $code => $card) {
            if (in_array($code, $selected)) {
                $result[$code] = $card;
            }
        }
        return $result;
    }

    public function getPayboxData() {
        return unserialize($this->getInfo()->getPbxepAuthorization());
    }

    /**
     * @return Paybox_Epayment_Model_Config Paybox configuration object
     */
    public function getPayboxConfig() {
        return Mage::getSingleton('pbxep/config');
    }

    public function getCardImageUrl() {
        $data = $this->getPayboxData();
        $cards = $this->getCreditCards();
        if (isset($cards[$data['cardType']])) {
            $card = $cards[$data['cardType']];
            return $this->getSkinUrl($card['image'], array('_area' => 'frontend'));
        }
        return $this->getSkinUrl('images/pbxep/'.strtolower($data['cardType']).'.45.png', array('_area' => 'frontend'));
    }

    public function getCardImageLabel() {
        $data = $this->getPayboxData();
        $cards = $this->getCreditCards();
        if (!isset($cards[$data['cardType']])) {
            return null;
        }
        $card = $cards[$data['cardType']];
        return $card['label'];
    }

    public function isAuthorized() {
        $info = $this->getInfo();
        $auth = $info->getPbxepAuthorization();
        return !empty($auth);
    }

    public function canCapture() {
        $info = $this->getInfo();
        $capture = $info->getPbxepCapture();
        $config = $this->getPayboxConfig();
        if ($config->getSubscription() == Paybox_Epayment_Model_Config::SUBSCRIPTION_FLEXIBLE) {
            if ($info->getPbxepAction() == Paybox_Epayment_Model_Payment_Abstract::PBXACTION_MANUAL) {
                $order = $info->getOrder();
                return empty($capture) && $order->canInvoice();
            }
        }
        return false;
    }

    public function canRefund() {
        $info = $this->getInfo();
        $capture = $info->getPbxepCapture();
        $config = $this->getPayboxConfig();
        if ($config->getSubscription() == Paybox_Epayment_Model_Config::SUBSCRIPTION_FLEXIBLE) {
            return !empty($capture);
        }
        return false;
    }

    public function getDebitTypeLabel() {
        $info = $this->getInfo();
        $action = $info->getPbxepAction();
        if (is_null($action) || ($action == 'three-time')) {
            return null;
        }

        $actions = Mage::getSingleton('pbxep/admin_payment_action')->toOptionArray();
        $result = $actions[$action]['label'];
        if (($info->getPbxepAction() == Paybox_Epayment_Model_Payment_Abstract::PBXACTION_DEFERRED) && (!is_null($info->getPbxepDelay()))) {
            $delays = Mage::getSingleton('pbxep/admin_payment_delays')->toOptionArray();
            $result .= ' (' . $delays[$info->getPbxepDelay()]['label'] . ')';
        }
        return $result;
    }

    public function getShowInfoToCustomer() {
        $config = $this->getPayboxConfig();
        return $config->getShowInfoToCustomer() != 0;
    }

    public function getThreeTimeLabels() {
        $info = $this->getInfo();
        $action = $info->getPbxepAction();
        if (is_null($action) || ($action != 'three-time')) {
            return null;
        }

        $result = array(
            'first' => $this->__('Not achieved'),
            'second' => $this->__('Not achieved'),
            'third' => $this->__('Not achieved'),
            'fourth' => $this->__('Not achieved'),
        );

        $data = $info->getPbxepFirstPayment();
        if (!empty($data)) {
            $data = unserialize($data);
            $date = preg_replace('/^([0-9]{2})([0-9]{2})([0-9]{4})$/', '$1/$2/$3', $data['date']);
            $result['first'] = $this->__('%s (%s)', $data['amount'] / 100.0, $date);
        }
        $data = $info->getPbxepSecondPayment();
        if (!empty($data)) {
            $data = unserialize($data);
            $date = preg_replace('/^([0-9]{2})([0-9]{2})([0-9]{4})$/', '$1/$2/$3', $data['date']);
            $result['second'] = $this->__('%s (%s)', $data['amount'] / 100.0, $date);
        }
        $data = $info->getPbxepThirdPayment();
        if (!empty($data)) {
            $data = unserialize($data);
            $date = preg_replace('/^([0-9]{2})([0-9]{2})([0-9]{4})$/', '$1/$2/$3', $data['date']);
            $result['third'] = $this->__('%s (%s)', $data['amount'] / 100.0, $date);
        }
        $data = $info->getPbxepFourthPayment();
        if (!empty($data)) {
            $data = unserialize($data);
            $date = preg_replace('/^([0-9]{2})([0-9]{2})([0-9]{4})$/', '$1/$2/$3', $data['date']);
            $result['fourth'] = $this->__('%s (%s)', $data['amount'] / 100.0, $date);
        }
        return $result;
    }

    public function getPartialCaptureUrl() {
        $info = $this->getInfo();
        return Mage::helper("adminhtml")->getUrl("*/sales_order_invoice/start", array(
                    'order_id' => $info->getOrder()->getId(),
        ));
    }

    public function getCaptureUrl() {
        $data = $this->getPayboxData();
        $info = $this->getInfo();
        return Mage::helper("adminhtml")->getUrl("*/pbxep/invoice", array(
                    'order_id' => $info->getOrder()->getId(),
                    'transaction' => $data['transaction'],
        ));
    }

    public function getRefundUrl() {
        $info = $this->getInfo();
        $order = $info->getOrder();
        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            if ($invoice->canRefund()) {
                return Mage::helper("adminhtml")->getUrl("*/sales_order_creditmemo/new", array(
                            'order_id' => $order->getId(),
                            'invoice_id' => $invoice->getId(),
                ));
            }
        }
        return null;
    }

}
