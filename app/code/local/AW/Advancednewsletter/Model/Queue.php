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


class AW_Advancednewsletter_Model_Queue extends Mage_Newsletter_Model_Queue
{
    const STATUS_NEVER = 0;
    const STATUS_SENDING = 1;
    const STATUS_CANCEL = 2;
    const STATUS_SENT = 3;
    const STATUS_PAUSE = 4;

    /**
     * Default design area for emulation
     */
    const DEFAULT_DESIGN_AREA = 'frontend';

    protected $_template;

    protected function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/queue');
    }

    /**
     * Retrieve Newsletter Template object
     *
     * @return Mage_Newsletter_Model_Template
     */
    public function getTemplate()
    {
        if (is_null($this->_template)) {
            $this->_template = Mage::getModel('advancednewsletter/template')
                ->load($this->getTemplateId())
            ;
        }
        return $this->_template;
    }

    /**
     * Returns subscribers collection for this queue
     *
     * @return Varien_Data_Collection_Db
     */
    public function getSubscribersCollection()
    {
        if (is_null($this->_subscribersCollection)) {
            $this->_subscribersCollection = Mage::getResourceModel('advancednewsletter/subscriber_collection')
                    ->useQueue($this);
        }
        return $this->_subscribersCollection;
    }

    /**
     * Send messages to subscribers for this queue
     *
     * @param int   $count
     * @param array $additionalVariables
     *
     * @return $this|Mage_Newsletter_Model_Queue
     */
    public function sendPerSubscriber($count = 20, array $additionalVariables = array())
    {
        if (
            $this->getQueueStatus() != self::STATUS_SENDING
            && ($this->getQueueStatus() != self::STATUS_NEVER && $this->getQueueStartAt())
        ) {
            return $this;
        }

        $collection = $this->getSubscribersCollection()
            ->useOnlyUnsent()
            ->showCustomerInfo()
            ->setPageSize($count)
            ->setCurPage(1)
            ->load()
        ;

        if (!$this->getTemplate()) {
            $this->addTemplateData($this);
            if (!$this->getTemplate()->isPreprocessed()) {
                $this->getTemplate()->preproccess();
            }
        }

        // save current design settings
        $currentDesignConfig = clone $this->_getDesignConfig();
        foreach ($collection->getItems() as $item) {
            if ($this->_getDesignConfig()->getStore() != $item->getStoreId()) {
                $this->_setDesignConfig(array('area' => self::DEFAULT_DESIGN_AREA, 'store' => $item->getStoreId()));
                $this->_applyDesignConfig();
            }
            $externalUrl = $this->_getViewWebVersionUrl($this->getTemplate(), $item);
            $emailSent = $this->getTemplate()->send($item, array('subscriber' => $item, 'external_url' => $externalUrl), null, $this);
            if (!$emailSent) {
                /* skip failed emails */
                $item->received($this);
            }
        }

        // restore previous design settings
        $this->_setDesignConfig($currentDesignConfig->getData());
        $this->_applyDesignConfig();

        if (count($collection->getItems()) < $count - 1 || count($collection->getItems()) == 0) {
            $this->setQueueFinishAt(now());
            $this->setQueueStatus(self::STATUS_SENT);
            $this->save();
        }
        return $this;
    }

    /**
     * Setter for design changes
     *
     * @param   array $config
     * @return  Mage_Newsletter_Model_Queue
     */
    protected function _setDesignConfig(array $config)
    {
        $this->_getDesignConfig()->setData($config);
        return $this;
    }

    /**
     * Getter for design changes
     *
     * @return Varien_Object
     */
    protected function _getDesignConfig()
    {
        if (is_null($this->_designConfig)) {
            $store = Mage::getDesign()->getStore();
            if (is_object($store)) {
                $store = $store->getId();
            }
            $this->_designConfig = new Varien_Object(
                array(
                    'area' => Mage::getDesign()->getArea(),
                    'store' => $store
                )
            );
        }
        return $this->_designConfig;
    }

    protected function _applyDesignConfig()
    {
        $designConfig = $this->_getDesignConfig();

        $design = Mage::getDesign();
        $designConfig->setOldArea($design->getArea())
                ->setOldStore($design->getStore());

        if ($designConfig->hasData('area')) {
            Mage::getDesign()->setArea($designConfig->getArea());
        }

        if ($designConfig->hasData('store')) {
            $store = $designConfig->getStore();
            Mage::app()->setCurrentStore($store);

            $locale = new Zend_Locale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $store));
            Mage::app()->getLocale()->setLocale($locale);
            Mage::app()->getLocale()->setLocaleCode($locale->toString());
            if ($designConfig->hasData('area')) {
                Mage::getSingleton('core/translate')->setLocale($locale)
                        ->init($designConfig->getArea(), true);
            }

            $design->setStore($store);
            $design->setTheme('');
            $design->setPackageName('');
        }
        return $this;
    }

    protected function _getViewWebVersionUrl($template, $subscriberModel)
    {
        $text = $template->getProcessedTemplate(array('subscriber' => $subscriberModel), true);
        $currentDate = Mage::app()->getLocale()
            ->date()
            ->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE)
            ->toString(Zend_Date::W3C)
        ;
        $storedModel = Mage::getModel('advancednewsletter/storedEmails');
        $storedModel
            ->setEmail($subscriberModel->getEmail())
            ->setContent($text)
            ->setCreatedAt($currentDate)
            ->setStoreId($subscriberModel->getStoreId())
            ->save()
        ;
        $externalUrl = Mage::helper('advancednewsletter')->getViewWebVersionUrl($subscriberModel, $storedModel->getId());
        return $externalUrl;
    }
}