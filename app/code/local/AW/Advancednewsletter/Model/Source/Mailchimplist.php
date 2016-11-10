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


class AW_AdvancedNewsletter_Model_Source_Mailchimplist
{

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

        $xmlrpcurl = Mage::getStoreConfig('advancednewsletter/mailchimpconfig/xmlrpc', $store);
        $apikey = Mage::getStoreConfig('advancednewsletter/mailchimpconfig/apikey', $store);

        if (!$apikey || !$xmlrpcurl)
            return array();

        try {
            $arr = explode('-', $apikey, 2);
            $dc = (isset($arr[1])) ? $arr[1] : 'us1';

            list($aux, $host) = explode('http://', $xmlrpcurl);
            $apiHost = 'http://' . $dc . '.' . $host;

            $client = new Zend_XmlRpc_Client($apiHost);

            /*
             *   Mailchimp API 1.3
             *   lists(string apikey, [array filters], [int start], [int limit])
             *
             */

            $lists = $client->call('lists', $apikey);
        } catch (Exception $e) {
             // "Test connection" button is responsible now for connection check
            return array();
        }

        if (is_array($lists) && isset($lists['data']) && count($lists['data'])) {

            $options = array();
            $options[] = array(
                'label' => 'Select a list..',
                'value' => '',
            );

            foreach ($lists['data'] as $list) {
                $options[] = array(
                    'value' => $list['id'],
                    'label' => $list['name']
                );
            }
            return $options;
        }
        return array();
    }

}