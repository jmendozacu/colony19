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


class AW_Advancednewsletter_Model_Rule_Condition_Order_Params extends Mage_Rule_Model_Condition_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this
            ->setType('advancednewsletter/rule_condition_order_params')
            ->setValue(null)
        ;
    }

    public function loadAttributeOptions()
    {
        $hlp = Mage::helper('advancednewsletter');
        $this->setAttributeOption(
            array(
                'store' => $hlp->__('Store'),
                'category' => $hlp->__('Category'),
                'order_status' => $hlp->__('Order status'),
                'sku' => $hlp->__('Contains any of these SKUs'),
            )
        );
        return $this;
    }

    public function getValueSelectOptions()
    {
        switch ($this->getAttribute()) {
            case 'store':
                $options = Mage::helper('advancednewsletter')->getStoresForRule();
                break;
            case 'category':
                $options = Mage::helper('advancednewsletter')->getCategoriesArray();
                foreach ($options as $key => $option) {
                    $options[$key]['label'] = str_replace('&nbsp;', '', $option['label']);
                }
                break;
            case 'order_status':
                $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
                $options = array();
                foreach ($statuses as $value => $label) {
                    $options[] = array(
                        'label' => $label,
                        'value' => $value,
                    );
                }
                break;
            default:
                $options = array();
                break;
        }

        $this->setData('value_select_options', $options);
        return $this->getData('value_select_options');
    }

    public function loadOperatorOptions()
    {
        $this->setOperatorOption(
            array(
                '==' => Mage::helper('advancednewsletter')->__('is'),
                '!=' => Mage::helper('advancednewsletter')->__('is not')
            )
        );
        return $this;
    }

    public function asHtml()
    {
        if ($this->getAttribute() == 'sku') {
            $html = $this->getTypeElement()->getHtml() .
                    Mage::helper('advancednewsletter')->__(
                        "%s %s", $this->getAttributeElement()->getHtml(), $this->getValueElement()->getHtml()
                    )
            ;
            if ($this->getId() != '1') {
                $html.= $this->getRemoveLinkHtml();
            }
            return $html;
        }
        return parent::asHtml();
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    public function getValueElementType()
    {
        if (in_array($this->getAttribute(), array('store', 'category', 'order_status'))) {
            return 'select';
        }
        return 'text';
    }

    public function validate(Varien_Object $object)
    {
        if ($this->getAttribute() == 'sku') {
            $sku = explode(',', $this->getValue());
            foreach ($sku as $skuA) {
                foreach ($object->getSku() as $skuB) {
                    if ($skuA == $skuB)
                        return true;
                }
            }
            return false;
        }

        if ($this->getAttribute() == 'category') {
            if (is_array($object->getCategories())) {
                foreach ($object->getCategories() as $key => $value) {
                    $result = $this->validateAttribute($value);
                    if ($result) {
                        return $result;
                    }
                }
            } else {
                return $this->validateAttribute($object->getCategories());
            }
        }
        return parent::validate($object);
    }

}