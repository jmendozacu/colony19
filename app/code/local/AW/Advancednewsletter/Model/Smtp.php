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


class AW_Advancednewsletter_Model_Smtp extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/smtp');
    }

    /**
     * Getting Smtp Array as array('label' => .., 'value' => ..)
     * @param bool $withNone
     *
     * @return array
     */
    public function getSmtpArray($withNone = false)
    {
        $smtpArray = array();
        if ($withNone) {
            $smtpArray[] = array('label' => 'None', 'value' => 0);
        }
        foreach ($this->getCollection() as $item) {
            $smtpArray[] = array('label' => $item->getTitle(), 'value' => $item->getSmtpId());
        }
        return $smtpArray;
    }

    /**
     * Getting Segments Array as array('label' => value)
     * @param bool $withNone
     *
     * @return array
     */
    public function getSmtpOptionArray($withNone = false)
    {
        $smtpArray = array();
        if ($withNone) {
            $smtpArray[0] = 'None';
        }
        foreach ($this->getCollection() as $item) {
            $smtpArray[$item->getSmtpId()] = $item->getTitle();
        }
        return $smtpArray;
    }

}