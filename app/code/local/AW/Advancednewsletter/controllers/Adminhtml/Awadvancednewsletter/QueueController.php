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


class AW_Advancednewsletter_Adminhtml_Awadvancednewsletter_QueueController extends Mage_Adminhtml_Controller_Action
{

    protected function displayTitle()
    {
        if (!Mage::helper('advancednewsletter')->magentoLess14())
            $this->_title($this->__('AdvancedNewsletter'))->_title($this->__('Newsletter Queue'));
        return $this;
    }

    /**
     * Queue list action
     */
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }

        $this
            ->displayTitle()
            ->loadLayout()
        ;

        $this->_setActiveMenu('advancednewsletter/queue');
        $this->_addBreadcrumb(
            Mage::helper('newsletter')->__('Newsletter Queue'), Mage::helper('newsletter')->__('Newsletter Queue')
        );
        $this->renderLayout();
    }

    /**
     * Queue list Ajax action
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('advancednewsletter/adminhtml_queue_grid')->toHtml()
        );
    }

    /*
     *  set sending status, update startTime
     */

    public function startAction()
    {
        $queue = $this->_getQueue();
        if (in_array($queue->getQueueStatus(), array(Mage_Newsletter_Model_Queue::STATUS_NEVER))) {
            $queue->setQueueStartAt(Mage::getSingleton('core/date')->gmtDate())
                ->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_SENDING)
                ->save()
            ;
        }
        $this->_redirect('*/*');
    }

    /*
     *  massStart Action
     */

    public function massStartAction()
    {
        $queueIds = $this->getRequest()->getParam('queue_id');
        if (!is_array($queueIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $doneCnt = 0;
                foreach ($queueIds as $queueId) {
                    $queue = Mage::getSingleton('advancednewsletter/queue')->load($queueId);
                    if (in_array($queue->getQueueStatus(), array(Mage_Newsletter_Model_Queue::STATUS_NEVER))) {
                        $queue->setQueueStartAt(Mage::getSingleton('core/date')->gmtDate())
                            ->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_SENDING)
                            ->save()
                        ;
                        $doneCnt++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully started', $doneCnt)
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*');
    }

    /*
     *  set pause status
     */

    public function pauseAction()
    {
        $queue = $this->_getQueue();
        $mageNewsletterStatuses = array(
            Mage_Newsletter_Model_Queue::STATUS_SENDING,
            Mage_Newsletter_Model_Queue::STATUS_NEVER,
        );
        if (in_array($queue->getQueueStatus(), $mageNewsletterStatuses)) {
            $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_PAUSE);
            $queue->save();
        }
        $this->_redirect('*/*');
    }

    /*
     *   massPauseAction
     *   delete only   STATUS_SENT && STATUS_CANCEL  queues
     *
     */

    public function massPauseAction()
    {
        $queueIds = $this->getRequest()->getParam('queue_id');
        if (!is_array($queueIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $doneCnt = 0;
                foreach ($queueIds as $queueId) {
                    $queue = Mage::getSingleton('advancednewsletter/queue')->load($queueId);
                    $mageNewsletterStatuses = array(
                        Mage_Newsletter_Model_Queue::STATUS_SENDING,
                        Mage_Newsletter_Model_Queue::STATUS_NEVER,
                    );
                    if (in_array($queue->getQueueStatus(), $mageNewsletterStatuses)) {
                        $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_PAUSE);
                        $queue->save();
                        $doneCnt++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully paused', $doneCnt)
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*');
    }

    /*
     *  update status for paused queue
     */

    public function resumeAction()
    {

        $queue = $this->_getQueue();

        if (in_array($queue->getQueueStatus(), array(Mage_Newsletter_Model_Queue::STATUS_PAUSE))) {

            /*
             *  if the date of the start of the queue is less than the current - you need to send letters now,
             *  or determine the STATUS_NEVER (was not sent yet)
             */

            if (strtotime($queue->getQueueStartAt()) <= strtotime(Mage::getModel('core/date')->gmtDate())) {
                $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_SENDING);
            } else {
                $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_NEVER);
            }
            $queue->save();
        }
        $this->_redirect('*/*');
    }

    /*
     *   massResumeAction
     */

    public function massResumeAction()
    {
        $queueIds = $this->getRequest()->getParam('queue_id');
        if (!is_array($queueIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $doneCnt = 0;
                foreach ($queueIds as $queueId) {
                    $queue = Mage::getSingleton('advancednewsletter/queue')->load($queueId);
                    if (in_array($queue->getQueueStatus(), array(Mage_Newsletter_Model_Queue::STATUS_PAUSE))) {
                        if (strtotime($queue->getQueueStartAt()) <= strtotime(Mage::getModel('core/date')->gmtDate())) {
                            $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_SENDING);
                        } else {
                            $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_NEVER);
                        }
                        $queue->save();
                        $doneCnt++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully resumed', $doneCnt)
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*');
    }

    /*
     *  set cancel status for queue
     */

    public function cancelAction()
    {
        $queue = $this->_getQueue();
        $mageNewsletterStatuses = array(
            Mage_Newsletter_Model_Queue::STATUS_NEVER,
            Mage_Newsletter_Model_Queue::STATUS_SENDING,
            Mage_Newsletter_Model_Queue::STATUS_PAUSE,
        );
        if (in_array($queue->getQueueStatus(), $mageNewsletterStatuses)) {
            $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_CANCEL);
            $queue->save();
        }

        $this->_redirect('*/*');
    }

    /*
     *   massCancelAction
     */

    public function massCancelAction()
    {
        $queueIds = $this->getRequest()->getParam('queue_id');
        if (!is_array($queueIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $doneCnt = 0;
                foreach ($queueIds as $queueId) {
                    $queue = Mage::getSingleton('advancednewsletter/queue')->load($queueId);
                    $mageNewsletterStatuses = array(
                        Mage_Newsletter_Model_Queue::STATUS_NEVER,
                        Mage_Newsletter_Model_Queue::STATUS_SENDING,
                        Mage_Newsletter_Model_Queue::STATUS_PAUSE,
                    );
                    if (in_array($queue->getQueueStatus(), $mageNewsletterStatuses)) {
                        $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_CANCEL);
                        $queue->save();
                        $doneCnt++;
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully canceled', $doneCnt)
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*');
    }

    public function sendingAction()
    {
        // Todo: put it somewhere in config!
        $countOfQueue = 3;
        $countOfSubscritions = 20;
        $collection = Mage::getResourceModel('advancednewsletter/queue_collection')
                ->setPageSize($countOfQueue)
                ->setCurPage(1)
                ->addOnlyForSendingFilter()
                ->load()
        ;
        $collection->walk('sendPerSubscriber', array($countOfSubscritions));
    }

    /* delete action */
    public function deleteAction()
    {
        $queue = $this->_getQueue();
        $allowedStatusList = array(
            Mage_Newsletter_Model_Queue::STATUS_SENT,
            Mage_Newsletter_Model_Queue::STATUS_CANCEL,
        );
        if (in_array($queue->getQueueStatus(), $allowedStatusList)) {
            $queue->delete();
        }
        $this->_redirect('*/*');
    }

    /**
     * Delete only STATUS_SENT && STATUS_CANCEL queues
     */
    public function massDeleteAction()
    {
        $queueIds = $this->getRequest()->getParam('queue_id');
        if (!is_array($queueIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $doneCnt = 0;
                $allowedStatusList = array(
                    Mage_Newsletter_Model_Queue::STATUS_SENT,
                    Mage_Newsletter_Model_Queue::STATUS_CANCEL,
                );
                foreach ($queueIds as $queueId) {
                    $queue = Mage::getSingleton('advancednewsletter/queue')->load($queueId);
                    if (in_array($queue->getQueueStatus(), $allowedStatusList)) {
                        $queue->delete();
                        $doneCnt++;
                    }
                }
                if ($doneCnt > 0) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('%d record(s) were successfully deleted', $doneCnt)
                    );
                }
                if (count($queueIds) != $doneCnt) {
                    Mage::getSingleton('adminhtml/session')->addNotice(
                        Mage::helper('adminhtml')->__(
                            '%s records were not deleted because only "%s" and "%s" items can be deleted',
                            count($queueIds) - $doneCnt,
                            Mage::helper('newsletter')->__('Sent'),
                            Mage::helper('newsletter')->__('Cancelled')
                        )
                    );
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*');
    }

    public function editAction()
    {
        $this->displayTitle();

        Mage::register('current_queue', Mage::getSingleton('advancednewsletter/queue'));
        $id = $this->getRequest()->getParam('id');
        $templateId = $this->getRequest()->getParam('template_id');
        if ($id) {
            Mage::registry('current_queue')->load($id);
        } elseif ($templateId) {
            $template = Mage::getModel('advancednewsletter/template')->load($templateId)->preprocess();
            Mage::registry('current_queue')->setTemplateId($template->getId());
        }

        $this->loadLayout();

        $this->_setActiveMenu('advancednewsletter/queue');

        $this->_addBreadcrumb(
            Mage::helper('newsletter')->__('Newsletter Queue'),
            Mage::helper('newsletter')->__('Newsletter Queue'),
            $this->getUrl('*/advancednewsletter_queue')
        );
        $this->_addBreadcrumb(
            Mage::helper('newsletter')->__('Edit Queue'), Mage::helper('newsletter')->__('Edit Queue')
        );

        $this->_addContent(
            $this->getLayout()->createBlock('advancednewsletter/adminhtml_queue_edit', 'queue.edit')
        );

        $this->renderLayout();
    }

    public function saveAction()
    {
        try {
            // create new queue from template, if queueId is not specified

            $queueId = (int) $this->getRequest()->getParam('queue_id');
            $templateId = $this->getRequest()->getParam('template_id');

            if ($queueId) {
                $queue = Mage::getSingleton('advancednewsletter/queue')
                        ->load($this->getRequest()->getParam('queue_id'));
            } else {
                $template = Mage::getModel('advancednewsletter/template')->load($templateId);
                if (!$template->getId() || $template->getIsSystem()) {
                    Mage::throwException($this->__('Wrong newsletter template.'));
                }
                $template->preprocess();
                $template->save();

                $queue = Mage::getModel('advancednewsletter/queue')
                    ->setTemplateId($template->getId())
                    ->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_NEVER)
                ;
            }

            $mageNewsletterStatuses = array(
                Mage_Newsletter_Model_Queue::STATUS_NEVER,
                Mage_Newsletter_Model_Queue::STATUS_PAUSE
            );
            if (!in_array($queue->getQueueStatus(), $mageNewsletterStatuses)) {
                $this->_redirect('*/*');
                return;
            }

            $format = Mage::app()->getLocale()->getDateTimeFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
            );

            if ($queue->getQueueStatus() == Mage_Newsletter_Model_Queue::STATUS_NEVER) {
                if ($this->getRequest()->getParam('start_at')) {
                    $date = Mage::app()->getLocale()->date($this->getRequest()->getParam('start_at'), $format);
                    $time = $date->getTimestamp();
                    $queue->setQueueStartAt(
                        Mage::getModel('core/date')->gmtDate(null, $time)
                    );
                } else {
                    $queue->setQueueStartAt(null);
                }
            }

            $queue->setStores($this->getRequest()->getParam('stores', array()));

            $queue->addTemplateData($queue);

            $queue->getTemplate()
                ->setTemplateSubject($this->getRequest()->getParam('subject'))
                ->setTemplateSenderName($this->getRequest()->getParam('sender_name'))
                ->setTemplateSenderEmail($this->getRequest()->getParam('sender_email'))
                ->setTemplateTextPreprocessed($this->getRequest()->getParam('text'))
            ;

            if ($queue->getQueueStatus() == Mage_Newsletter_Model_Queue::STATUS_PAUSE
                && $this->getRequest()->getParam('_resume', false)) {
                $queue->setQueueStatus(Mage_Newsletter_Model_Queue::STATUS_SENDING);
            }

            $queue->setSaveTemplateFlag(true);
            $queue->save();

            $this->_redirect('*/*');
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $this->_redirect('*/*/edit', array('id' => $id));
            } else {
                $this->_redirectReferer();
            }
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('advancednewsletter/queue');
    }

    private function _getQueue()
    {
        $queue = Mage::getSingleton('advancednewsletter/queue')
            ->load($this->getRequest()->getParam('id'))
        ;
        return $queue;
    }

}
