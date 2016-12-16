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


class AW_Advancednewsletter_Block_Adminhtml_Form_Element_Testconnection extends Mage_Adminhtml_Block_Template
{
    /**
     * Path to template
     */
    const TEMPLATE_PATH = 'advancednewsletter/form/element/testconnection.phtml';

    /**
     * Basical states
     * @var array
     */
    protected $_states = array(
        array(
            'value' => AW_Advancednewsletter_Model_Form_Element_Testconnection::STATUS_SUCCESS,
            'label' => 'Successed',
            'color' => 'green'
        ),
        array(
            'value' => AW_Advancednewsletter_Model_Form_Element_Testconnection::STATUS_FAIL,
            'label' => 'Failed',
            'color' => 'red'
        ),
    );

    /**
     * Class constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate(self::TEMPLATE_PATH);
    }


    /**
     * Retrives translated and colored label
     * @param string $label Label
     * @param string $color Color of label
     * @return string
     */
    public function renderStateHtml($label, $color)
    {
        $label = Mage::helper('advancednewsletter')->__($label);
        return "<strong style=\"color: {$color};\">{$label}</strong>";
    }

    /**
     * Retrives array of States
     * @return array
     */
    public function getStateObjects()
    {
        $states = array();
        foreach ($this->_states as $state) {
            $states[] = new Varien_Object($state);
        }
        return $states;
    }
}