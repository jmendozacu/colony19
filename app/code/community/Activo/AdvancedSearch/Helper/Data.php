<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

class Activo_AdvancedSearch_Helper_Data extends Mage_CatalogSearch_Helper_Data
{

    public function getSimilarWords($word, $level=1)
    {
        return Mage::getSingleton('advancedsearch/dictionary')->getSimilarWords($word, $level);
    }
    
}