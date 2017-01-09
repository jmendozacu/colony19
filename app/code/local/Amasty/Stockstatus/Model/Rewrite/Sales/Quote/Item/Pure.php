<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */


if (Mage::helper('core')->isModuleEnabled('Amasty_Promo') && substr((string)Mage::getConfig()->getNode()->modules->Amasty_Promo->version, 0, 1) >= 2) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Stockstatus_Model_Rewrite_Sales_Quote_Item_Promo');
}
else {
    class Amasty_Stockstatus_Model_Rewrite_Sales_Quote_Item_Pure extends Mage_Sales_Model_Quote_Item {}
}
