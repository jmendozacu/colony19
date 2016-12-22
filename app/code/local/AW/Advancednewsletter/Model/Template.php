<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancednewsletter
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancednewsletter_Model_Template extends Mage_Newsletter_Model_Template
{
    const TEST_EMAIL = 'advancednewsletter/general/test_email';

    /**
     * Configuration of desing package for template
     * @var Varien_Object
     */
    protected $_designConfig;


    /**
     * Configuration of emulated desing package.
     * @var Varien_Object|boolean
     */
    protected $_emulatedDesignConfig = false;

    /**
     * Initial environment information
     * @var Varien_Object|null
     */
    protected $_initialEnvironmentInfo = null;

    public function _construct()
    {
        $this->_init('advancednewsletter/template');
    }

    /**
     * Sending activation mail
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return AW_Advancednewsletter_Model_Template
     */
    public function sendActivationMail($subscriber)
    {
        $subscriber->setConfirmationLink(
            Mage::getUrl(
                'advancednewsletter/index/activate',
                array('confirm_code' => $subscriber->getConfirmCode(), 'email' => $subscriber->getEmail())
            )
        );
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        Mage::getModel('core/email_template')->sendTransactional(
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRM_EMAIL_TEMPLATE),
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_CONFIRM_EMAIL_IDENTITY),
            $subscriber->getEmail(),
            $subscriber->getLastName() . " " . $subscriber->getFirstName(),
            array('subscriber' => $subscriber)
        );
        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Sending first subscription mail
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return AW_Advancednewsletter_Model_Template
     */
    public function sendFirstSubscribeMail($subscriber)
    {
        $subscriber->setUnsubscribeAllLink($subscriber->getUnsubscribeAllLink());

        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        Mage::getModel('core/email_template')->sendTransactional(
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE),
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_SUCCESS_EMAIL_IDENTITY),
            $subscriber->getEmail(),
            $subscriber->getLastName() . " " . $subscriber->getFirstName(),
            array('subscriber' => $subscriber)
        );
        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Sending unsubscribe from all mail
     * @param AW_Advancednewsletter_Model_Subscriber $subscriber
     * @return AW_Advancednewsletter_Model_Template
     */
    public function sendUnsubscribeMail($subscriber)
    {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        Mage::getModel('core/email_template')->sendTransactional(
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE),
            Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_IDENTITY),
            $subscriber->getEmail(),
            $subscriber->getLastName() . " " . $subscriber->getFirstName(),
            array('subscriber' => $subscriber)
        );
        $translate->setTranslateInline(true);
        return $this;
    }

    /*
     * Retrieve included template
     *
     * @param string $templateCode
     * @param array $variables
     * @return string
     */

    public function getInclude($templateCode, array $variables)
    {
        return Mage::getModel('advancednewsletter/template')
            ->loadByCode($templateCode)
            ->getProcessedTemplate($variables)
        ;
    }

    /**
     * Load template by code
     *
     * @param string $templateCode
     * @return Mage_Newsletter_Model_Template
     */
    public function loadByCode($templateCode)
    {
        $this->_getResource()->loadByCode($this, $templateCode);
        return $this;
    }

    /**
     * Send mail to subscriber
     *
     * @param   AW_Advancednewsletter_Model_Subscriber|string $subscriber   subscriber Model or E-mail
     * @param   array $variables    template variables
     * @param   string|null $name     receiver name (if subscriber model not specified)
     * @param   Mage_Newsletter_Model_Queue|null $queue  queue model
     *
     * @return boolean
     * */
    public function send(
        $subscriber, array $variables = array(), $name = null, Mage_Newsletter_Model_Queue $queue = null
    )
    {
        if (!$this->isValidForSend()) {
            return false;
        }

        $email = '';
        if ($subscriber instanceof AW_Advancednewsletter_Model_Subscriber) {
            $email = $subscriber->getEmail();
            $name = $subscriber->getFirstName() . ' ' . $subscriber->getLastName();
            $variables['awunsubscribefromsegment'] = $this->getUnsubscriptionSegmentLink($subscriber);
            $variables['awunsubscribe'] = '<a href="' . $subscriber->getUnsubscriptionLink() . '">'
                . Mage::helper('advancednewsletter')->__('Unsubscribe') . '</a>';
            $variables['awloginpage'] = '<a href="' . Mage::app()->getStore($subscriber->getStoreId())->getBaseUrl()
                . 'customer/account/login/">'
                . Mage::helper('advancednewsletter')->__('Manage subscriptions') . '</a>';
        } else {
            $email = (string) $subscriber;
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
            $variables['awunsubscribe'] = $subscriber->getId()
                ?
                '<a href="' . $subscriber->getUnsubscriptionLink() . '">'
                . Mage::helper('advancednewsletter')->__('Unsubscribe') . '</a>'
                :
                '<p style="text-decoration:underline;">' . Mage::helper('advancednewsletter')
                    ->__('You can unsubscribe from our newsletter in your account.')
                . '</p>';
            $variables['awloginpage'] = '<a href="' . Mage::getBaseUrl()
                . 'customer/account/login/">'
                . Mage::helper('advancednewsletter')->__('Manage subscriptions') . '</a>';
            $variables['awunsubscribefromsegment'] = $this->getUnsubscriptionSegmentLink($subscriber);
        }
        $variables['subscriber'] = $subscriber;


        if (Mage::getStoreConfigFlag(Mage_Newsletter_Model_Subscriber::XML_PATH_SENDING_SET_RETURN_PATH)) {
            $this->getMail()->setReturnPath($this->getTemplateSenderEmail());
        }

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();
        $mail->addTo($email, $name);

        $storeId = (int)Mage::app()->getRequest()->getParam('store_id');
        if(!$storeId) {
            $storeId = Mage::app()->getAnyStoreView()->getId();
        }
        $this->emulateDesign($storeId);
        $text = $this->getProcessedTemplate($variables, true);
        $this->revertDesign();

        if ($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }

        try {
            $mail->setSubject($this->getProcessedTemplateSubject($variables));
            $mail->setFrom($this->getTemplateSenderEmail(), $this->getTemplateSenderName());
        } catch (Exception $ex) {

        }

        $smtp = Mage::getModel('advancednewsletter/smtp')->load($this->getSmtpId());
        $transport = null;
        if ($smtp->getId()) {
            if ($smtp->getUsessl()) {
                $config = array(
                    'ssl' => 'tls',
                    'port' => $smtp->getPort(),
                    'auth' => 'login',
                    'username' => $smtp->getUserName(),
                    'password' => $smtp->getPassword()
                );
            } else {
                $config = array(
                    'port' => $smtp->getPort(),
                    'auth' => 'login',
                    'username' => $smtp->getUserName(),
                    'password' => $smtp->getPassword()
                );
            }
            $config = array(
                'port'     => $smtp->getPort(),
                'auth'     => 'login',
                'username' => $smtp->getUserName(),
                'password' => $smtp->getPassword()
            );
            if ($smtp->getUsessl() == 1) {
                $config['ssl']  = 'tls';
                $config['port'] = $smtp->getPort();
            } else if ($smtp->getUsessl() == 2){
                $config['ssl']  = 'ssl';
                $config['port'] = $smtp->getPort();
            }
            $transport = new Zend_Mail_Transport_Smtp($smtp->getServerName(), $config);
        }

        try {
            if ($transport) {
                $mail->send($transport);
            } else {
                $mail->send();
            }
            $this->_mail = null;
            if (!is_null($queue)) {
                $subscriber->received($queue);
            }
        } catch (Exception $e) {
            Mage::helper('awcore/logger')->log($this, $subscriber->getEmail().' - '.$e->getMessage());
            return false;
        }
        return true;
    }

    public function getTemplateText()
    {
        if (!$this->getData('template_text') && !$this->getId()) {
            $this->setData(
                'template_text',
                Mage::helper('newsletter')->__('Follow this link to unsubscribe {{var awunsubscribe}}')
            );
        }

        return $this->getData('template_text');
    }

    public function getSegments() {
        $collection = Mage::getModel('advancednewsletter/segment')->getCollection();
        $collection->addFieldToFilter('code', array('in' => $this->getSegmentsCodes()));
        return $collection;
    }

    public function getUnsubscriptionSegmentLink($subscriber)
    {
        return Mage::getUrl(
            "advancednewsletter/index/unsubscribe",
            array(
                'email' => $subscriber->getEmail(),
                'segments_codes' => implode(',', $this->getSegmentsCodes()),
                'key' => Mage::helper('advancednewsletter')->encrypt($subscriber->getId()),
                '_store' => $subscriber->getStoreId()
            )
        );
    }

    public function emulateDesign($storeId, $area='frontend')
    {
        if ($this->_isVersionLt15x()) {
            if ($storeId) {
                // save current design settings
                $this->_emulatedDesignConfig = clone $this->getDesignConfig();
                if ($this->getDesignConfig()->getStore() != $storeId) {
                    $this->setDesignConfig(array('area' => $area, 'store' => $storeId));
                    $this->_applyDesignConfig();
                }
            } else {
                $this->_emulatedDesignConfig = false;
            }
        }
        else {
            parent::emulateDesign($storeId);
        }
    }

    public function revertDesign()
    {
        if ($this->_isVersionLt15x()) {
            if ($this->_emulatedDesignConfig) {
                $this->setDesignConfig($this->_emulatedDesignConfig->getData());
                $this->_cancelDesignConfig();
                $this->_emulatedDesignConfig = false;
            }
        }
        else {
            parent::revertDesign();
        }
    }

    protected function _isVersionLt15x() {
        return preg_match('/^1.[0-4]/', Mage::getVersion());
    }

    protected function getDesignConfig()
    {
        if ($this->_isVersionLt15x()) {
            if(is_null($this->_designConfig)) {
                $store = Mage::getDesign()->getStore();
                $storeId = is_object($store) ? $store->getId() : $store;
                $this->_designConfig = new Varien_Object(array(
                    'area' => Mage::getDesign()->getArea(),
                    'store' => $storeId
                ));
            }
            return $this->_designConfig;
        }
        else {
            return parent::getDesignConfig();
        }
    }

    public function setDesignConfig(array $config)
    {
        if ($this->_isVersionLt15x()) {
            $this->getDesignConfig()->setData($config);
            return $this;
        }
        else {
            return parent::setDesignConfig($config);
        }
    }

    protected function _applyDesignConfig()
    {
        if ($this->_isVersionLt15x()) {
            $designConfig = $this->getDesignConfig();
            $store = $designConfig->getStore();
            $storeId = is_object($store) ? $store->getId() : $store;
            $area = $designConfig->getArea();
            if (!is_null($storeId)) {
                $appEmulation = Mage::getSingleton('advancednewsletter/app_emulation');
                $this->_initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId, $area);
            }
            return $this;
        }
        else {
            return parent::_applyDesignConfig();
        }
    }

    protected function _cancelDesignConfig()
    {
        if ($this->_isVersionLt15x()) {
            if (!empty($this->_initialEnvironmentInfo)) {
                $appEmulation = Mage::getSingleton('advancednewsletter/app_emulation');
                $appEmulation->stopEnvironmentEmulation($this->_initialEnvironmentInfo);
                $this->_initialEnvironmentInfo = null;
            }
            return $this;
        }
        else {
            return parent::_cancelDesignConfig();
        }
    }
}
