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


class AW_Advancednewsletter_Block_Adminhtml_Queue_Grid_Renderer_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{

    public function render(Varien_Object $row)
    {
        $actions = array();

        $helper = Mage::helper('newsletter');
        $rowId = $row->getId();

        /*
         *
         *     const STATUS_NEVER = 0;
         *     const STATUS_SENDING = 1;
         *     const STATUS_CANCEL = 2;
         *     const STATUS_SENT = 3;
         *     const STATUS_PAUSE = 4;
         *
         */

        switch ($row->getQueueStatus()) {
            case Mage_Newsletter_Model_Queue::STATUS_NEVER:

                /*  start now */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/start', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to start the queue now?'),
                    'caption' => $helper->__('Start now')
                );

                /*   cancel */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/cancel', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to cancel the queue?'),
                    'caption' => $helper->__('Cancel')
                );

                break;


            case Mage_Newsletter_Model_Queue::STATUS_SENT:

                /* delete */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/delete', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to delete the queue?'),
                    'caption' => $helper->__('Delete')
                );

                break;

            case Mage_Newsletter_Model_Queue::STATUS_SENDING:

                /* pause */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/pause', array('id' => $rowId)),
                    'caption' => $helper->__('Pause')
                );

                /* cancel */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/cancel', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to cancel the queue?'),
                    'caption' => $helper->__('Cancel')
                );

                break;


            case Mage_Newsletter_Model_Queue::STATUS_CANCEL:

                /* delete */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/delete', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to delete the queue?'),
                    'caption' => $helper->__('Delete')
                );


                break;


            case Mage_Newsletter_Model_Queue::STATUS_PAUSE:

                /* resume */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/resume', array('id' => $rowId)),
                    'caption' => $helper->__('Resume')
                );

                /* cancel */
                $actions[] = array(
                    'url' => $this->getUrl('*/*/cancel', array('id' => $rowId)),
                    'confirm' => $helper->__('Do you really want to cancel the queue?'),
                    'caption' => $helper->__('Cancel')
                );

                break;

            default:
                break;
        }

        /* preview */
        $actions[] = array(
            'url' => $this->getUrl('*/awadvancednewsletter_template/preview', array('id' => $row->getTemplateId())),
            'caption' => $helper->__('Preview'),
            'popup' => true
        );

        $this->getColumn()->setActions($actions);
        return parent::render($row);
    }

}
