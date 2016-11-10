<?php

/**
 * Iksanika llc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.iksanika.com/products/IKS-LICENSE.txt
 *
 * @category   Iksanika
 * @package    Iksanika_Stockmanage
 * @copyright  Copyright (c) 2013 Iksanika llc. (http://www.iksanika.com)
 * @license    http://www.iksanika.com/products/IKS-LICENSE.txt
 */

class Iksanika_Stockmanage_Helper_Data 
    extends Mage_Core_Helper_Abstract 
{
    public function getStoreId()
    {
        return (int) Mage::app()->getRequest()->getParam('store', 0);
    }
    
    public function getStore()
    {
        return Mage::app()->getStore($this->getStoreId());
    }
        
    public function isVersionNew($current, $new)
    {
        $isNew = false;
        
        $cur = explode('.', $current);
        $new = explode('.', $new);
        
        if((int)$new[0] > (int)$cur[0])
        {
            $isNew = true;
        }
        
        if((int)$new[1] > (int)$cur[1])
        {
            $isNew = true;
        }
        
        if((int)$new[2] > (int)$cur[2])
        {
            $isNew = true;
        }
        
        return $isNew;
    }

}
