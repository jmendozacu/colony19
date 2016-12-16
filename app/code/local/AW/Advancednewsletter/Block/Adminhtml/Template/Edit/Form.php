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


class AW_Advancednewsletter_Block_Adminhtml_Template_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{

    public function getModel()
    {
        return Mage::registry('an_current_template');
    }

    protected function _prepareForm()
    {
        $model = $this->getModel();
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            )
        );

        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => Mage::helper('newsletter')->__('Template Information'),
                'class' => 'fieldset-wide aw_advancednewsletter_edit_template'
            )
        );

        if ($model->getId()) {
            $fieldset->addField(
                'id', 'hidden',
                array(
                    'name' => 'id',
                    'value' => $model->getId(),
                )
            );
        }

        $fieldset->addField(
            'code', 'text',
            array(
                'name' => 'code',
                'label' => Mage::helper('newsletter')->__('Template Name'),
                'title' => Mage::helper('newsletter')->__('Template Name'),
                'required' => true,
                'value' => $model->getTemplateCode(),
            )
        );

        $fieldset->addField(
            'subject', 'text',
            array(
                'name' => 'subject',
                'label' => Mage::helper('newsletter')->__('Template Subject'),
                'title' => Mage::helper('newsletter')->__('Template Subject'),
                'required' => true,
                'value' => $model->getTemplateSubject(),
            )
        );

        $fieldset->addField(
            'sender_name', 'text',
            array(
                'name' => 'sender_name',
                'label' => Mage::helper('newsletter')->__('Sender Name'),
                'title' => Mage::helper('newsletter')->__('Sender Name'),
                'required' => true,
                'value' => $model->getTemplateSenderName(),
            )
        );

        $fieldset->addField(
            'sender_email', 'text',
            array(
                'name' => 'sender_email',
                'label' => Mage::helper('newsletter')->__('Sender Email'),
                'title' => Mage::helper('newsletter')->__('Sender Email'),
                'class' => 'validate-email',
                'required' => true,
                'value' => $model->getTemplateSenderEmail(),
            )
        );

        $fieldset->addField(
            'segments_codes', 'multiselect',
            array(
                'name'     => 'segments_codes',
                'label'    => Mage::helper('newsletter')->__('Segments Codes'),
                'title'    => Mage::helper('newsletter')->__('Segments Codes'),
                'required' => true,
                'values'   => Mage::getModel('advancednewsletter/segment')->getSegmentArray(),
                'value'    => $model->getSegmentsCodes(),
                'note'     => Mage::helper('advancednewsletter')->addSelectAll('segments_codes'),
            )
        );

        if (Mage::helper('advancednewsletter')->extensionEnabled('AW_Marketsuite')) {
            $afterElementHtml = Mage::helper('advancednewsletter')->__(
                'Send newsletter to customers according to the MSS rule'
            );
            $fieldset->addField(
                'mss_rule_id', 'select',
                array(
                    'name'     => 'mss_rule_id',
                    'label'    => Mage::helper('advancednewsletter')->__('MSS Rule'),
                    'title'    => Mage::helper('advancednewsletter')->__('MSS Rule'),
                    'required' => true,
                    'values'   => Mage::helper('advancednewsletter')->getMssRulesOptionArray(),
                    'note'     => $afterElementHtml,
                )
            );
        } else {
            $advertisementText = Mage::helper('advancednewsletter')->__(
                'You can use Market Segmentation Suite rules to send your templates'
            );
            $afterElementHtml = Mage::helper('advancednewsletter')->getMssAdvertisementText($advertisementText);
            $fieldset->addField('mss_rule_id', 'label', array('after_element_html' => $afterElementHtml));
        }

        $fieldset->addField(
            'smtp_id', 'select',
            array(
                'name' => 'smtp_id',
                'label' => Mage::helper('newsletter')->__('SMTP account'),
                'title' => Mage::helper('newsletter')->__('SMTP account'),
                'required' => true,
                'values' => Mage::getModel('advancednewsletter/smtp')->getSmtpArray(true),
                'value' => $model->getSmtpId()
            )
        );

        if (Mage::helper('advancednewsletter')->magentoLess14()) {
            $fieldset->addField(
                'text', 'editor',
                array(
                    'name' => 'text',
                    'label' => Mage::helper('newsletter')->__('Template Content'),
                    'title' => Mage::helper('newsletter')->__('Template Content'),
                    'required' => true,
                    'state' => 'html',
                    'style' => 'height:36em;',
                    'value' => $model->getTemplateText()
                )
            );
        } else {
            $widgetFilters = array('is_email_compatible' => 1);
            $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig(
                array('widget_filters' => $widgetFilters)
            );
            $wysiwygConfig->setData(
                Mage::helper('advancednewsletter')->recursiveReplace(
                    '/advancednewsletter_admin/',
                    '/' . (string) Mage::app()->getConfig()->getNode('admin/routers/adminhtml/args/frontName') . '/',
                    $wysiwygConfig->getData()
                )
            );
            if ($model->isPlain()) {
                $wysiwygConfig->setEnabled(false);
            }
            $fieldset->addField(
                'text', 'editor', array(
                    'name' => 'text',
                    'label' => Mage::helper('newsletter')->__('Template Content'),
                    'title' => Mage::helper('newsletter')->__('Template Content'),
                    'required' => true,
                    'state' => 'html',
                    'style' => 'height:36em;',
                    'value' => $model->getTemplateText(),
                    'config' => $wysiwygConfig
                )
            );

            if (!$model->isPlain()) {
                $fieldset->addField(
                    'template_styles', 'textarea',
                    array(
                        'name' => 'styles',
                        'label' => Mage::helper('newsletter')->__('Template Styles'),
                        'container_id' => 'field_template_styles',
                        'value' => $model->getTemplateStyles()
                    )
                );
            }
        }

        $form->setAction($this->getUrl('*/*/save'));
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

}