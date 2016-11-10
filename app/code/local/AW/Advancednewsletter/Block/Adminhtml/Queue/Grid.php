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


class AW_Advancednewsletter_Block_Adminhtml_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('queueGrid');
        $this->setDefaultSort('start_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('queue_id');
        $this->getMassactionBlock()->setFormFieldName('queue_id');

        $this->getMassactionBlock()->addItem(
            'pause',
            array(
                'label' => Mage::helper('newsletter')->__('Pause'),
                'url' => $this->getUrl('*/*/massPause', array('' => '')),
                'confirm' => Mage::helper('newsletter')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'start',
            array(
                'label' => Mage::helper('newsletter')->__('Start'),
                'url' => $this->getUrl('*/*/massStart', array('' => '')),
                'confirm' => Mage::helper('newsletter')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'cancel',
            array(
                'label' => Mage::helper('newsletter')->__('Cancel'),
                'url' => $this->getUrl('*/*/massCancel', array('' => '')),
                'confirm' => Mage::helper('newsletter')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'resume',
            array(
                'label' => Mage::helper('newsletter')->__('Resume'),
                'url' => $this->getUrl('*/*/massResume', array('' => '')),
                'confirm' => Mage::helper('newsletter')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label' => Mage::helper('newsletter')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete', array('' => '')),
                'confirm' => Mage::helper('newsletter')->__('Are you sure?')
            )
        );
        return $this;
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('advancednewsletter/queue_collection')
            ->addTemplateInfo()
            ->addSubscribersInfo()
        ;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'queue_id',
            array(
                'header' => Mage::helper('newsletter')->__('ID'),
                'index' => 'queue_id',
                'width' => 10
            )
        );

        $this->addColumn(
            'start_at',
            array(
                'header' => Mage::helper('newsletter')->__('Queue Start'),
                'type' => 'datetime',
                'index' => 'queue_start_at',
                'gmtoffset' => true,
                'default' => ' ---- '
            )
        );

        $this->addColumn(
            'finish_at',
            array(
                'header' => Mage::helper('newsletter')->__('Queue Finish'),
                'type' => 'datetime',
                'index' => 'queue_finish_at',
                'gmtoffset' => true,
                'default' => ' ---- '
            )
        );

        $this->addColumn(
            'template_subject',
            array(
                'header' => Mage::helper('newsletter')->__('Subject'),
                'index' => 'template_subject'
            )
        );

        /*
         *     const STATUS_NEVER = 0;
         *     const STATUS_SENDING = 1;
         *     const STATUS_CANCEL = 2;
         *     const STATUS_SENT = 3;
         *     const STATUS_PAUSE = 4;
         */

        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('newsletter')->__('Status'),
                'index' => 'queue_status',
                'type' => 'options',
                'options' => array(
                    Mage_Newsletter_Model_Queue::STATUS_SENT => Mage::helper('newsletter')->__('Sent'),
                    Mage_Newsletter_Model_Queue::STATUS_CANCEL => Mage::helper('newsletter')->__('Cancelled'),
                    Mage_Newsletter_Model_Queue::STATUS_NEVER => Mage::helper('newsletter')->__('Not Sent'),
                    Mage_Newsletter_Model_Queue::STATUS_SENDING => Mage::helper('newsletter')->__('Sending'),
                    Mage_Newsletter_Model_Queue::STATUS_PAUSE => Mage::helper('newsletter')->__('Paused'),
                ),
                'width' => '100px',
            )
        );

        $this->addColumn(
            'subscribers_sent',
            array(
                'header' => Mage::helper('newsletter')->__('Processed'),
                'type' => 'number',
                'index' => 'subscribers_sent'
            )
        );

        $this->addColumn(
            'subscribers_total',
            array(
                'header' => Mage::helper('newsletter')->__('Recipients'),
                'type' => 'number',
                'index' => 'subscribers_total'
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('newsletter')->__('Action'),
                'filter' => false,
                'sortable' => false,
                'no_link' => true,
                'width' => '100px',
                'renderer' => 'advancednewsletter/adminhtml_queue_grid_renderer_action'
            )
        );
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
