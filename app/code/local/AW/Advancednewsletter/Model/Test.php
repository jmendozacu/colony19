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


class AW_Advancednewsletter_Model_Test extends Mage_Core_Model_Abstract
{

    public function testSubscribe()
    {
        echo "Testing subscribe <br/>";
        $email = 'tester46@gmail.com';
        $segments = array('a', 'b', 'c');
        $params = array('first_name' => 'Sergey', 'last_name' => 'Pypkin', 'phone' => '02', 'store_id' => '1');

        echo "Start <br/>";
        var_dump(Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->getData());

        //***************** Subscribing
        echo "Subscribing <br/>";
        Mage::getModel('advancednewsletter/subscriber')->subscribe($email, $segments, $params);
        /**///************* End Subscribing

        echo "After subscribing <br/>";
        var_dump(Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->getData());
        $code = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->getConfirmCode();

        //***************** Activate
        echo "Activating <br/>";
        Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->activate($code);
        /**///************* End activate
        //***************** Unsubscribing
        /* Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->unsubscribe($segmentsUns);
          /* *///************* End Unsubscribing
        //***************** Unsubscribing from all
        /* Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->unsubscribeFromAll();
          /* *///************* End Unsubscribing from all

        echo "End <br/>";
        var_dump(Mage::getModel('advancednewsletter/subscriber')->loadByEmail($email)->getData());
    }

}