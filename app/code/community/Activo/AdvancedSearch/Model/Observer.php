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
class Activo_AdvancedSearch_Model_Observer
{

    public function catalogSearchEvent($observer)
    {
        Mage::getModel('advancedsearch/query')->graphDataOperation();
        return $this;
    }

}
