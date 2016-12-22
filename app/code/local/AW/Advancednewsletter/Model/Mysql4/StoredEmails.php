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


class AW_Advancednewsletter_Model_Mysql4_StoredEmails extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('advancednewsletter/stored_emails', 'id');
    }

    public function getStoredEmail($email, $storedEmailId)
    {
        $storedEmailsCollection = Mage::getModel('advancednewsletter/storedEmails')->getCollection();
        $storedEmailsCollection
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('id', $storedEmailId)
        ;
        return $storedEmailsCollection->getFirstItem();
    }

    public function removeOldStoredEmails()
    {
        $storeCollection = Mage::getModel('core/store')->getCollection();
        $writeAdapter = $this->_getWriteAdapter();
        foreach ($storeCollection as $storeModel) {
            $storedEmailsLifeTime = (int)Mage::getStoreConfig('advancednewsletter/general/stored_emails_lifetime', $storeModel);
            if ($storedEmailsLifeTime > 0) {
                $finalDay = $currentDate = Mage::app()->getLocale()
                    ->date()
                    ->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE)
                    ->subDay($storedEmailsLifeTime)
                    ->toString(Zend_Date::W3C)
                ;
                $writeAdapter->query("DELETE FROM `{$this->getTable('advancednewsletter/stored_emails')}` "
                    . "WHERE created_at  <= '" . $finalDay . "' AND store_id = {$storeModel->getId()}"
                );
            }
        }
        return $this;
    }
}