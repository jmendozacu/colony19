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
 * @version    2.4.7
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


/**
 * DEPRICATED. Was used in AW_Advancednewsletter < 2.0. Now used for sync with 2.0 
 * version and compatibility with other extensions
 */
class AW_Advancednewsletter_Model_Subscriptions extends Mage_Core_Model_Abstract
{

    protected $_subscriber;

    /**
     * DEPRICATED
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/subscriptions');
    }

    /**
     * DEPRICATED
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param int $salutation
     * @param string $phone
     * @param array $segmentsArray
     */
    public function subscribe($email, $firstname, $lastname, $salutation, $phone, $segmentsArray)
    {
        if (in_array(AW_Advancednewsletter_Helper_Data::AN_SEGMENTS_ALL, $segmentsArray)) {
            $segmentsArray = Mage::getModel('advancednewsletter/segment')->toOptionArray();
        }
        $params = array(
            'first_name' => $firstname, 'last_name' => $lastname, 'phone' => $phone, 'salutation' => $salutation
        );
        return Mage::getModel('advancednewsleter/subscriber')->subscribe($email, $segmentsArray, $params);
    }

    /**
     * @deprecated
     * @param string $email
     *
     * @return $this
     */
    public function getSubscriber($email)
    {
        $this->_subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email);
        return $this;
    }

    /**
     * @deprecated
     * @return string 
     */
    public function getSegmentsCodes()
    {
        if ($this->_subscriber) {
            return implode(',', $this->subscriber->getSegmentsCodes());
        }
        return '';
    }

}