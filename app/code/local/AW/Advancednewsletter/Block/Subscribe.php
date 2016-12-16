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


class AW_Advancednewsletter_Block_Subscribe extends Mage_Core_Block_Template
{
    const STYLES_PATH = 'advancednewsletter/formconfiguration/segmentsstyle';

    const DISPLAY_NAMES = 'advancednewsletter/formconfiguration/displayname';
    const DISPLAY_PHONE = 'advancednewsletter/formconfiguration/displayphone';
    const DISPLAY_SALUTATION = 'advancednewsletter/formconfiguration/displaysalutation';
    const SALUTATION_FIRST = 'advancednewsletter/formconfiguration/salutation1';
    const SALUTATION_SECOND = 'advancednewsletter/formconfiguration/salutation2';

    protected $_subscriber;
    protected static $_uniqueId = 0;

    protected static $_segments;

    protected function _toHtml()
    {
        if (!$this->getSegments()->count()) {
            return '';
        }

        if ($this->getInsertedManually()) {
            $layout = Mage::getModel('core/layout');
            $checkboxes = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/options/checkboxes.phtml')
            ;
            $radio = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/options/radio.phtml')
            ;
            $none = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/options/none.phtml')
            ;
            $multiselect = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/options/multiselect.phtml')
            ;
            $select = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/options/select.phtml')
            ;
            $data = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/subscriber/data.phtml')
            ;
            $subscribeBlock = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/subscribe.phtml')
            ;
            $subscribeBlock
                    ->setChild('advancednewsletter.options.checkboxes', $checkboxes)
                    ->setChild('advancednewsletter.options.radio', $radio)
                    ->setChild('advancednewsletter.options.none', $none)
                    ->setChild('advancednewsletter.options.multiselect', $multiselect)
                    ->setChild('advancednewsletter.options.select', $select)
                    ->setChild('advancednewsletter.subscriber.data', $data);
            $subscribeLink = $layout->createBlock('advancednewsletter/subscribe')
                ->setTemplate('advancednewsletter/subscribe_link.phtml')
            ;

            $main = $layout->createBlock('advancednewsletter/subscribe', 'advancednewsletter.subscribe.block')
                    ->setTemplate('advancednewsletter/subscribe_block.phtml')
                    ->setData('in_block_only', $this->getInBlockOnly() ? true : false);
            $main
                    ->setChild('advancednewsletter.subscribe', $subscribeBlock)
                    ->setChild('advancednewsletter.subscribe.link', $subscribeLink);
            return $main->renderView();
        }
        return parent::_toHtml();
    }

    public function getBlockUniqueId()
    {
        return self::$_uniqueId;
    }

    public function setBlockUniqueId($id)
    {
        self::$_uniqueId = $id;
    }

    public function getSubscriber()
    {
        if (!$this->_subscriber) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getSingleton('customer/session')->getId());
            $this->_subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($customer->getEmail());
        }
        return $this->_subscriber;
    }

    public function getSegments()
    {
        if (!self::$_segments) {
            self::$_segments = Mage::getModel('advancednewsletter/segment')
                ->getCollection()
                ->addStoreFilter($this->getStoreId())
                ->addCategoryFilter($this->getCategoryId())
                ->addFieldToFilter('frontend_visibility', array('eq' => array(1)))
                ->addOrder('display_order', Varien_Data_Collection::SORT_ORDER_ASC)
            ;
        }
        return self::$_segments;
    }

    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    public function getCategoryId()
    {
        return Mage::helper('advancednewsletter')->getCategoryId();
    }

    public function displaySalutation()
    {
        return Mage::getStoreConfig(self::DISPLAY_SALUTATION);
    }

    public function displayNames()
    {
        return Mage::getStoreConfig(self::DISPLAY_NAMES) && !Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function displayPhone()
    {
        return Mage::getStoreConfig(self::DISPLAY_PHONE);
    }

    public function displayEmail()
    {
        return!Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getAjaxUrl()
    {
        return $this->getUrl(
            "advancednewsletter/index/subscribeajax",
            array(
                 'an_category_id' => $this->getCategoryId(),
                 '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()
            )
        );
    }

    public function checkDisplay()
    {
        return $this->getSegments()->getSize() > 0
            || Mage::getStoreConfig(self::STYLES_PATH) == AW_Advancednewsletter_Model_Source_Segmentsstyle::STYLE_NONE
        ;
    }

    public function displayLabel()
    {
        if ($this->getDisplayLabel() == 'false') {
            return false;
        }
        return true;
    }

}