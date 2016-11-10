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


class AW_Advancednewsletter_Block_Adminhtml_Subscriber_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('subscriberGrid');
        $this->setDefaultSort('id', 'desc');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('advancednewsletter/subscriber_collection')
            ->addCustomerType()
            ->showStoreInfo()
            ->joinCustomerTable()
        ;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('advancednewsletter');
        $this->addColumn(
            'id',
            array(
                'header' => $helper->__('ID'),
                'index' => 'id'
            )
        );

        $this->addColumn(
            'customer_type',
            array(
                'header' => $helper->__('Type'),
                'index' => 'customer_type',
                'type' => 'options',
                'options' => array(
                    0 => $helper->__('Guest'),
                    1 => $helper->__('Customer')
                )
            )
        );

        $this->addColumn(
            'first_name',
            array(
                'header' => $helper->__('First name'),
                'index' => 'first_name'
            )
        );

        $this->addColumn(
            'last_name',
            array(
                'header' => $helper->__('Last name'),
                'index' => 'last_name'
            )
        );

        $this->addColumn(
            'email',
            array(
                'header' => $helper->__('Email'),
                'index' => 'email',
                'filter_condition_callback' => array($this, '_emailFilterCallback')
            )
        );

        $groups = Mage::getResourceModel('customer/group_collection')
            ->load()
            ->toOptionHash()
        ;
        $this->addColumn(
            'customer_group_id',
            array(
                 'filter_index' => 'IF(ce.group_id IS NULL, 0, ce.group_id)',
                 'header'       => Mage::helper('customer')->__('Group'),
                 'width'        => '100',
                 'index'        => 'customer_group_id',
                 'type'         => 'options',
                 'options'      => $groups,
            )
        );

        $nHelper = Mage::helper('newsletter');
        $this->addColumn(
            'status',
            array(
                'header' => $helper->__('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => array(
                    AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE => $nHelper->__('Not Activated'),
                    AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED => $nHelper->__('Subscribed'),
                    AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED => $nHelper->__('Unsubscribed'),
                )
            )
        );

        $this->addColumn(
            'phone',
            array(
                'header' => $helper->__('Phone'),
                'index' => 'phone'
            )
        );

        $this->addColumn(
            'website',
            array(
                'header' => Mage::helper('newsletter')->__('Website'),
                'index' => 'website_id',
                'type' => 'options',
                'options' => Mage::getModel('adminhtml/system_store')->getWebsiteOptionHash(),
                'filter_condition_callback' => array($this, '_websiteFilterCallback')
            )
        );

        $this->addColumn(
            'group',
            array(
                'header' => Mage::helper('newsletter')->__('Store'),
                'index' => 'group_id',
                'type' => 'options',
                'options' => Mage::getModel('adminhtml/system_store')->getStoreGroupOptionHash(),
                'filter_condition_callback' => array($this, '_groupFilterCallback')
            )
        );

        $this->addColumn(
            'store_id',
            array(
                'header' => $helper->__('Store View'),
                'index' => 'store_id',
                'type' => 'options',
                'options' => Mage::getModel('adminhtml/system_store')->getStoreOptionHash(),
                'filter_condition_callback' => array($this, 'storeFilterCallback'),
            )
        );

        $this->addColumn(
            'segments_codes',
            array(
                'header' => $helper->__('Segments'),
                'index' => 'segments_codes',
                'renderer' => 'advancednewsletter/adminhtml_subscriber_grid_renderer_segments'
            )
        );

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('subscriber');

        $segments = Mage::getModel('advancednewsletter/segment')->getNonMssSegmentArray();
        $additional = array(
            'visibility' => array(
                'name' => 'segment',
                'type' => 'select',
                'class' => 'required-entry',
                'label' => Mage::helper('advancednewsletter')->__('Segment'),
                'values' => $segments
            )
        );

        $this->getMassactionBlock()->addItem(
            'subscribe',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Subscribe'),
                'url' => $this->getUrl('*/*/massSubscribe'),
                'additional' => $additional
            )
        );

        $this->getMassactionBlock()->addItem(
            'unsubscribe',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Unsubscribe'),
                'url' => $this->getUrl('*/*/massUnsubscribe'),
                'additional' => $additional
            )
        );

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label' => Mage::helper('advancednewsletter')->__('Delete'),
                'url' => $this->getUrl('*/*/massDelete'),
                'confirm' => Mage::helper('advancednewsletter')->__('Are you sure?'),
            )
        );
        return $this;
    }

    protected function storeFilterCallback($collection, $column)
    {
        $val = $column->getFilter()->getValue();
        if (is_null(@$val)) {
            return;
        }
        $collection->getSelect()->where('store.store_id=?', $val);
    }

    protected function _emailFilterCallback($collection, $column)
    {
        $val = $column->getFilter()->getValue();
        if (is_null(@$val)) {
            return;
        }
        if (strpos($val, '%') === false) {
            $val .= '%';
        }
        $collection->getSelect()->where('main_table.email LIKE ?', $val);
    }

    protected function _groupFilterCallback($collection, $column)
    {
        $val = $column->getFilter()->getValue();
        if (is_null(@$val)) {
            return;
        }
        $collection->getSelect()->where('store.group_id=?', $val);
    }

    protected function _websiteFilterCallback($collection, $column)
    {
        $val = $column->getFilter()->getValue();
        if (is_null(@$val)) {
            return;
        }
        $collection->getSelect()->where('store.website_id=?', $val);
    }

}
