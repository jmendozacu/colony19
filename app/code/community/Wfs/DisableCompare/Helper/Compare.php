<?php
/**
 * MageryThemes
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://magery-themes.com/MAGERY-LICENSE.txt
 *
 *
 * MAGENTO EDITION USAGE NOTICE
 *
 * This package designed for Magento COMMUNITY edition
 * MageryThemes does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * MageryThemes does not provide extension support in case of
 * incorrect edition usage.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *
 * @category   Wfs
 * @package    Wfs_DisableCompare
 * @copyright  Copyright (c) 2013 MageryThemes (http://magery-themes.com)
 * @license    http://magery-themes.com/MAGERY-LICENSE.txt
 */
class Wfs_DisableCompare_Helper_Compare extends Mage_Catalog_Helper_Product_Compare
{
    /**
     * Retrieve url for adding product to compare list
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getAddUrl($product)
    {
        return '';
    }
}