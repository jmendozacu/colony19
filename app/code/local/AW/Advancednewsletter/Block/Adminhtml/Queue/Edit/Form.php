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


class AW_Advancednewsletter_Block_Adminhtml_Queue_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $queue = Mage::registry('current_queue');

        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getUrl('*/*/save', array('queue_id' => $this->getRequest()->getParam('id'))),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            )
        );

        $fieldset = $form->addFieldset(
            'main_group', array('legend' => Mage::helper('advancednewsletter')->__('Fields'))
        );

        $outputFormat = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
        if ($queue->getQueueStatus() == Mage_Newsletter_Model_Queue::STATUS_NEVER) {
            $fieldset->addField(
                'date', 'date',
                array(
                    'name' => 'start_at',
                    'time' => true,
                    'format' => $outputFormat,
                    'label' => Mage::helper('newsletter')->__('Queue Date Start'),
                    'image' => $this->getSkinUrl('images/grid-cal.gif')
                )
            );
        } else {
            $fieldset->addField(
                'date', 'date',
                array(
                    'name' => 'start_at',
                    'time' => true,
                    'format' => $outputFormat,
                    'label' => Mage::helper('newsletter')->__('Queue Date Start'),
                    'image' => $this->getSkinUrl('images/grid-cal.gif')
                )
            );
        }

        $fieldset->addField(
            'template_id', 'hidden',
            array(
                'name' => 'template_id',
                'label' => Mage::helper('advancednewsletter')->__('Template Id'),
                'value' => $queue->getTemplateId()
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField(
                'stores', 'multiselect',
                array(
                    'name' => 'stores[]',
                    'label' => Mage::helper('newsletter')->__('Subscribers From'),
                    'image' => $this->getSkinUrl('images/grid-cal.gif'),
                    'required' => true,
                    'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
                    'value' => $queue->getStores()
                )
            );
        } else {
            $fieldset->addField(
                'stores', 'hidden',
                array(
                    'name' => 'stores[]',
                    'value' => Mage::app()->getStore(true)->getId()
                )
            );
        }

        if ($queue->getQueueStartAt()) {
            $form->getElement('date')->setValue(
                Mage::app()->getLocale()->date($queue->getQueueStartAt(), Varien_Date::DATETIME_INTERNAL_FORMAT)
            );
        }

        $fieldset->addField(
            'subject_readonly', 'text',
            array(
                'name' => 'subject_readonly',
                'label' => Mage::helper('newsletter')->__('Subject'),
                'disabled' => 'true',
                'value' => $queue->getTemplate()->getTemplateSubject()
            )
        );
        
        $fieldset->addField(
            'subject', 'hidden',
            array(
                'name' => 'subject',
                'value' => $queue->getTemplate()->getTemplateSubject()
            )
        );
        
        $fieldset->addField(
            'sender_name_readonly', 'text',
            array(
                'name' => 'sender_name_readonly',
                'label' => Mage::helper('newsletter')->__('Sender Name'),
                'title' => Mage::helper('newsletter')->__('Sender Name'),
                'disabled' => 'true',
                'value' => $queue->getTemplate()->getTemplateSenderName()
            )
        );
        
        $fieldset->addField(
            'sender_name', 'hidden',
            array(
                'name' => 'sender_name',
                'value' => $queue->getTemplate()->getTemplateSenderName()
            )
        );

        $fieldset->addField(
            'sender_email_readonly', 'text',
            array(
                'name' => 'sender_email_readonly',
                'label' => Mage::helper('newsletter')->__('Sender Email'),
                'title' => Mage::helper('newsletter')->__('Sender Email'),
                'class' => 'validate-email',
                'disabled' => 'true',
                'value' => $queue->getTemplate()->getTemplateSenderEmail()
            )
        );
        
        $fieldset->addField(
            'sender_email', 'hidden',
            array(
                'name' => 'sender_email',
                'value' => $queue->getTemplate()->getTemplateSenderEmail()
            )
        );

        $fieldset->addField(
            'text', 'editor',
            array(
                'name' => 'text',
                'label' => Mage::helper('newsletter')->__('Message'),
                'value' => $queue->getTemplate()->getProcessedTemplate(),
                'style' => 'width:98%; height: 200px;',
                'disabled' => 'true',
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

}