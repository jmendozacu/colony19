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


class AW_Advancednewsletter_Block_Adminhtml_Automanagement_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $model = Mage::registry('automanagement_data');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('rule_');

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
                ->setTemplate('promo/fieldset.phtml')
                ->setNewChildUrl(
                    $this->getUrl('*/awadvancednewsletter_automanagement/newConditionHtml/form/rule_conditions_fieldset')
                )
        ;

        $fieldset = $form
            ->addFieldset(
                'conditions_fieldset',
                array(
                    'legend' => Mage::helper('advancednewsletter')->__('Conditions')
                )
            )
            ->setRenderer($renderer)
        ;

        $fieldset->addField(
            'conditions', 'text',
            array(
                'name' => 'conditions',
                'label' => Mage::helper('advancednewsletter')->__('Conditions'),
                'title' => Mage::helper('advancednewsletter')->__('Conditions'),
                'required' => true,
            )
        )
            ->setRule($model)
            ->setRenderer(Mage::getBlockSingleton('rule/conditions'))
        ;

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

}