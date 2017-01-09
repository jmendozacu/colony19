<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */


if (Mage::helper('core')->isModuleEnabled('Amasty_Conf')) {
    $autoloader = Varien_Autoload::instance();
    $autoloader->autoload('Amasty_Stockstatus_Block_Rewrite_Product_View_Type_Configurable_Conf');
} else {
    class Amasty_Stockstatus_Block_Rewrite_Product_View_Type_Configurable_Pure extends Mage_Catalog_Block_Product_View_Type_Configurable {}
}
