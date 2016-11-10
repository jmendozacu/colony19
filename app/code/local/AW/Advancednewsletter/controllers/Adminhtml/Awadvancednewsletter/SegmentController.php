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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_SegmentController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14()) {
            $this->_title($this->__('Advanced Newsletter'))->_title($this->__('Segments'));
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
        $segment = Mage::getModel('advancednewsletter/segment')->load($this->getRequest()->getParam('id'));
        if ($segment->getId()) {
            Mage::register('an_current_segment', $segment);
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('Edit Segment');
        } else {
            $breadcrumbTitle = $breadcrumbLabel = Mage::helper('advancednewsletter')->__('New Segment');
        }

        Mage::getSingleton('adminhtml/session')->setAnSegmentData($segment);
        $this
            ->displayTitle()
            ->loadLayout()
        ;
        $this->_setActiveMenu('advancednewsletter');
        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);
        $this->_addContent($this->getLayout()->createBlock('advancednewsletter/adminhtml_segment_edit'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if (!$request->getParam('code') || !$request->getParam('title')
            || preg_match("/[<>, ]/", $request->getParam('code'))
            || preg_match("/[<>,]/", $request->getParam('title'))) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('advancednewsletter')->__(
                    'Segment code and title can not include \',<>\' and _ at the code beginning. '
                    . 'Spaces mustn\'t be used in segment code'
                )
            );
            return $this->_redirect('*/*/edit');
        }

        try {
            $model = Mage::getModel('advancednewsletter/segment');
            if ($request->getParam('segment_id'))
                $model->load($request->getParam('segment_id'));
            if (
                Mage::getModel('advancednewsletter/segment')->load($request->getParam('code'),'code')->getId()
                && !$request->getParam('segment_id')
            ) {
                Mage::throwException($this->__('Segment with the same code already exists'));
            }
            $model
                ->setData($request->getParams())
                ->save()
            ;
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('advancednewsletter')->__('Item was successfully saved')
            );
            if ($this->getRequest()->getParam('back'))
                return $this->_redirect('*/*/edit', array('id' => $model->getId()));
            return $this->_redirect('*/*/', array('id' => $model->getId()));
        } catch (Exception $ex) {
            Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            return $this->_redirect('*/*/edit');
        }
    }

    public function deleteAction()
    {
        $segmentId = $this->getRequest()->getParam('id');
        if ($segmentId) {
            try {
                Mage::getModel('advancednewsletter/segment')->load($segmentId)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('advancednewsletter')->__('Segment was successfully deleted')
                );
            } catch (Exception $ex) {
                Mage::getSingleton('adminhtml/session')->addError($ex->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $segmentIds = $this->getRequest()->getParam('segment');
        if (!is_array($segmentIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($segmentIds as $segmentId) {
                    Mage::getModel('advancednewsletter/segment')->load($segmentId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($segmentIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/segmentsmanagment');
    }

}