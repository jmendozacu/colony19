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
 * @package    AW_Zblocks
 * @version    2.5.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Model_Zblocks extends Mage_Core_Model_Abstract
{
    protected $_blocksCollection = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('zblocks/zblocks');
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        if (!is_null($this->getCustomerGroup())) {
            $this->setCustomerGroupIds(explode(',' , $this->getCustomerGroup()));
        }
    }

    public function getBlockCollection()
    {
        if (null === $this->_blocksCollection) {
            $this->_blocksCollection = $this->getCollection()->joinBlockCount()->addPositionColumn();
        }

        return $this->_blocksCollection;
    }

    /**
     * Add customer groups to model
     * @param array $groups
     * @return AW_Zblocks_Model_Zblocks 
     */    
    public function addCustomerGroups($groups)
    {
        if (!is_array($groups) || empty($groups)) {
            $this->setCustomerGroup(null);
            return false;
        }       
        $this->setCustomerGroup(implode(',' , $groups));
        return $this;
    }
}