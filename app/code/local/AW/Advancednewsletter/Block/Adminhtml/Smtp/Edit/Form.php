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


class AW_Advancednewsletter_Block_Adminhtml_Smtp_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $smtp = Mage::registry('an_current_smtp');

        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $fieldset = $form->addFieldset(
            'main_group', array('legend' => Mage::helper('advancednewsletter')->__('Fields'))
        );

        $fieldset->addField(
            'title', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Title'),
                'name' => 'title',
                'required' => true,
            )
        );

        $fieldset->addField(
            'server_name', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Server name'),
                'name' => 'server_name',
                'required' => true,
            )
        );
        $fieldset->addField(
            'user_name', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('User name'),
                'name' => 'user_name',
                'required' => true,
            )
        );
        $fieldset->addField(
            'password', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Password'),
                'name' => 'password',
                'required' => true,
            )
        );
        $fieldset->addField(
            'port', 'text',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Port'),
                'name' => 'port',
                'required' => true,
            )
        );
        $fieldset->addField(
            'usessl', 'select',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Use TLS or SSL'),
                'name' => 'usessl',
                'values' => array(
                    array(
                        'value' => 0,
                        'label' => Mage::helper('advancednewsletter')->__('No'),
                    ),
                    array(
                        'value' => 1,
                        'label' => Mage::helper('advancednewsletter')->__('TLS'),
                    ),
                    array(
                        'value' => 2,
                        'label' => Mage::helper('advancednewsletter')->__('SSL'),
                    )
                )
            )
        );

        # Test connection button
        $fieldset->addType('testconnection', 'AW_Advancednewsletter_Model_Form_Element_Testconnection');
        $fieldset->addField(
            'test_connection', 'testconnection',
            array(
                'label' => '',
                'name' => 'test_connection_button',
            )
        );

        if ($smtp) {
            $form->setValues($smtp);
        }
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}