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


class AW_AdvancedNewsletter_Model_Source_Mailchimplist
{
    /**
     * Get Mailchimp lists as options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $store = null;
        if (Mage::app()->getRequest()->getParam('store')) {
            $store = Mage::app()->getRequest()->getParam('store');
        } else if ($websiteParam = Mage::app()->getRequest()->getParam('website')) {
            $store = Mage::app()->getWebsite($websiteParam)->getDefaultStore()->getId();
        }

        if (!Mage::helper('advancednewsletter')->isChimpEnabled($store)) {
            return array();
        }

        try {
            $mailChimp = AW_Advancednewsletter_Model_Sync_Mailchimp::getInstance($store);
            $lists = $mailChimp->getAllLists();
        } catch (Exception $e) {
            return array();
        }

        $options = array();
        $options[] = array(
            'label' => Mage::helper('advancednewsletter')->__('Select a list..'),
            'value' => '',
        );

        foreach ($lists as $listId => $listName) {
            $options[] = array(
                'value' => $listId,
                'label' => $listName
            );
        }
        return $options;
    }
}
