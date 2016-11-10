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


class AW_Advancednewsletter_Block_Adminhtml_Subscriber_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $helper = Mage::helper('advancednewsletter');
        $currentSubscriber = Mage::registry('an_current_subscriber');

        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $fieldset = $form->addFieldset('main_group', array('legend' => $helper->__('Fields')));

        if ($currentSubscriber && $currentSubscriber->getId()) {
            $fieldset->addField(
                'id', 'hidden',
                array(
                'name' => 'id',
                'value' => $currentSubscriber->getId(),
                )
            );
        }

        $fieldset->addField(
            'first_name', 'text',
            array(
                'label' => $helper->__('First Name'),
                'name' => 'first_name',
            )
        );

        $fieldset->addField(
            'last_name', 'text',
            array(
                'label' => $helper->__('Last Name'),
                'name' => 'last_name',
            )
        );

        $fieldset->addField(
            'email', 'text',
            array(
                'label' => $helper->__('Email'),
                'name' => 'email',
                'required' => true,
                'class' => 'validate-email',
            )
        );

        $fieldset->addField(
            'phone', 'text',
            array(
                'label' => $helper->__('Phone'),
                'name' => 'phone',
            )
        );

        $fieldset->addField(
            'salutation', 'select',
            array(
                'label' => $helper->__('Salutation'),
                'name' => 'salutation',
                'values' => array('0' => $helper->__('Salutation 1'),
                    '1' => $helper->__('Salutation 2')
                )
            )
        );

        $fieldset->addField(
            'store_id', 'select',
            array(
                'label' => $helper->__('Subscribed in store'),
                'name' => 'store_id',
                'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
                'required' => true,
            )
        );

        $fieldset->addField(
            'segments_codes', 'multiselect',
            array(
                'name' => 'segments_codes',
                'label' => $helper->__('Segment'),
                'title' => $helper->__('Segment'),
                'values' => Mage::getModel('advancednewsletter/segment')->getSegmentArray(),
                'style' => 'display:block',
                'after_element_html' => Mage::helper('advancednewsletter')->addSelectAll('segments_codes')
            )
        );

        $fieldset->addField(
            'status', 'select',
            array(
                'name' => 'status',
                'label' => $helper->__('Status'),
                'title' => $helper->__('Status'),
                'values' => array(
                    AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE => $helper->__('Not Activated'),
                    AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED => $helper->__('Subscribed'),
                    AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => $helper->__('Unsubscribed'),
                ),
                'required' => true,
            )
        );

        if ($currentSubscriber) {
            $form->setValues($currentSubscriber);
        }
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}