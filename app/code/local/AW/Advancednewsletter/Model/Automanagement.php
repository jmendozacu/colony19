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


class AW_Advancednewsletter_Model_Automanagement extends Mage_Rule_Model_Rule
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancednewsletter/automanagement');
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('advancednewsletter/rule_condition_combine');
    }

    /**
     * Check custpmer to the automanagement rule
     * @param Mage_Customer_Model_Customer $toValidate
     */
    public function checkRule($toValidate)
    {
        $rules = Mage::getModel('advancednewsletter/automanagement')->getCollection();
        foreach ($rules as $rule) {
            if (!$rule->getStatus()) {
                continue;
            }
            $ruleValidate = Mage::getModel('advancednewsletter/automanagement')->load($rule->getRuleId());
            if ($ruleValidate->validate($toValidate)) {
                Mage::helper('advancednewsletter/subscriber')->updateSegments(
                    $toValidate->getOrder(), $ruleValidate->getSegmentsCut(), $ruleValidate->getSegmentsPaste()
                );
            }
        }
    }

}