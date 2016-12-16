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


class AW_Advancednewsletter_Block_Checkout_Subscribe extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
    }

    public function getAgreements()
    {
        return Mage::getBlockSingleton('checkout/agreements')->getAgreements();
    }

    /*
     *   get cubscriber's email stored in checkout quote
     *   @return mixed  string | NULL
     * 
     */

    public function getStoredEmail()
    {
        return $this->helper('advancednewsletter')->getCheckoutStoredEmail();
    }

    /*
     *  collection of segments visible on checkout
     * 
     */

    public function getSegmentsOnCheckout()
    {

        $segments = Mage::getModel('advancednewsletter/segment')
                ->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addFieldToFilter('checkout_visibility', array('eq' => array(1)))
                ->addOrder('display_order', Varien_Data_Collection::SORT_ORDER_ASC);
        return $segments;
    }

    /*
     *  all codes of segments visible on checkout
     * 
     */

    public function getSegmentsCodesOnCheckout()
    {
        return $this->helper('advancednewsletter')->getSegmentsCodesOnCheckout();
    }

    /*
     *   segment codes by selected email
     * 
     */

    public function getSegmentsCodesByEmail($email='')
    {
        if (!$email) {
            $email = $this->getStoredEmail();
        }
        $segmentsCodes = Mage::getSingleton('advancednewsletter/subscriber')
                ->loadByEmail($email)
                ->getData('segments_codes')
        ;
        return $segmentsCodes;
    }

}
