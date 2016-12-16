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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_TemplateController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Newsletter Templates'));
        }
        return $this;
    }

    public function indexAction()
    {
        $this
            ->displayTitle()
            ->loadLayout()
            ->_setActiveMenu('advancednewsletter')
            ->renderLayout()
        ;
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->displayTitle();

        $model = Mage::getModel('advancednewsletter/template');
        if ($id = $this->getRequest()->getParam('id')) {
            $model->load($id);
        }

        Mage::register('an_current_template', $model);

        $this->loadLayout();
        $this->_setActiveMenu('advancednewsletter/template');

        if ($model->getId()) {
            $breadcrumbTitle = Mage::helper('newsletter')->__('Edit Template');
            $breadcrumbLabel = $breadcrumbTitle;
        } else {
            $breadcrumbTitle = Mage::helper('newsletter')->__('New Template');
            $breadcrumbLabel = Mage::helper('newsletter')->__('Create Newsletter Template');
        }

        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

        // restore data
        if ($values = $this->_getSession()->getData('advancednewsletter_template_form_data', true)) {
            $model->addData($values);
        }

        if ($editBlock = $this->getLayout()->getBlock('an.template_edit')) {
            $editBlock->setEditMode($model->getId() > 0);
        }

        $this->renderLayout();
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        $template = Mage::getModel('advancednewsletter/template');

        if ($id = (int) $request->getParam('id')) {
            $template->load($id);
        }

        try {
            $template->addData($request->getParams())
                ->setTemplateSubject($request->getParam('subject'))
                ->setTemplateCode($request->getParam('code'))
                ->setTemplateSenderEmail($request->getParam('sender_email'))
                ->setTemplateSenderName($request->getParam('sender_name'))
                ->setTemplateText($request->getParam('text'))
                ->setTemplateStyles($request->getParam('styles'))
                ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
            ;

            if (!$template->getId()) {
                $template->setTemplateType(Mage_Newsletter_Model_Template::TYPE_HTML);
                $template->setAddedAt(Mage::getSingleton('core/date')->gmtDate());
            }
            if ($this->getRequest()->getParam('_change_type_flag')) {
                $template->setTemplateType(Mage_Newsletter_Model_Template::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            /* Market Segmentation Suite compatibility */
            if ($request->getParam('mss_rule_id')) {
                $rule = Mage::getModel('marketsuite/api')->getRule($request->getParam('mss_rule_id'));
                if ($rule->getId()) {
                    $newSegmentCode = 'Market_Segmentation_Suite_rule_' . $rule->getName();
                    if (!Mage::getModel('advancednewsletter/segment')->createNewMssSegment($newSegmentCode)) {
                        Mage::throwException(Mage::helper('newsletter')->__('Cannot create MSS rule segment.'));
                    }
                    $segmentCodeList = $template->getData('segments_codes');
                    $segmentCodeList[] = $newSegmentCode;
                    $template->setData('segments_codes', $segmentCodeList);
                    foreach (Mage::getModel('marketsuite/api')->exportCustomers($rule->getId()) as $customer) {
                        Mage::register('an_disable_autosync', true);
                        Mage::getModel('advancednewsletter/subscriber')
                            ->setCustomer(Mage::getModel('customer/customer')->load($customer->getId()))
                            ->subscribe($customer->getEmail(), array($newSegmentCode))
                        ;
                    }
                }
            }

            if ($this->getRequest()->getParam('_save_as_flag')) {
                $template->setId(null);
            }
            $template->preprocess();
            $this->_redirect('*/*');
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError(nl2br($e->getMessage()));
            $this->_getSession()->setData('advancednewsletter_template_form_data', $this->getRequest()->getParams());
        } catch (Exception $e) {
            $this->_getSession()->addException(
                $e, Mage::helper('adminhtml')->__('An error occurred while saving this template.')
            );
            $this->_getSession()->setData('advancednewsletter_template_form_data', $this->getRequest()->getParams());
        }
        $this->_forward('new');
    }

    public function deleteAction()
    {
        $template = Mage::getModel('advancednewsletter/template')
                ->load($this->getRequest()->getParam('id'));
        if ($template->getId()) {
            try {
                $template->delete();
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addException(
                    $e, Mage::helper('adminhtml')->__('An error occurred while deleting this template.')
                );
            }
        }
        $this->_redirect('*/*');
    }

    /**
     * Preview Newsletter template
     *
     */
    public function previewAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function sendtestAction()
    {
        $id = $this->getRequest()->getParam('id');
        $email = Mage::getStoreConfig(AW_Advancednewsletter_Model_Template::TEST_EMAIL);
        if (!$email) {
            $this->_getSession()->addError($this->__("Invalid test email"));
            return $this->_redirect('*/*');
        }

        try {
            if ($id) {
                if (Mage::getModel('advancednewsletter/template')->load($id)->preprocess()->send($email)) {
                    $this->_getSession()->addSuccess($this->__("Test email has been sent"));
                } else {
                    $this->_getSession()->addError($this->__("Test email has not been sent"));
                }
            } else {
                $this->_getSession()->addError($this->__("Invalid template"));
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        return $this->_redirect('*/*');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/templates');
    }

}