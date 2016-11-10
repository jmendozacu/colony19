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


class AW_Advancednewsletter_Block_Adminhtml_Segment_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('segmentGrid');
        $this->setDefaultSort('segment_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advancednewsletter/segment')->getCollection()
            ->joinSubscribers()
        ;
        $this->setCollection($collection);
        parent::_prepareCollection();
        $this->addAdditionalFields();
        return $this;
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('advancednewsletter');
        $this->addColumn(
            'segment_id',
            array(
                'header' => $helper->__('ID'),
                'index' => 'segment_id',
            )
        );

        $this->addColumn(
            'title',
            array(
                'header' => $helper->__('Title'),
                'index' => 'title',
            )
        );

        $this->addColumn(
            'code',
            array(
                'header' => $helper->__('Segment code'),
                'index' => 'code',
            )
        );

        $this->addColumn(
            'default_store',
            array(
                'header' => $helper->__('Default store'),
                'index' => 'default_store',
                'type' => 'options',
                'options' => $this->_getStoreOptions()
            )
        );

        $this->addColumn(
            'default_category',
            array(
                'header' => $helper->__('Default category'),
                'index' => 'default_category',
                'type' => 'options',
                'options' => $this->_getCategoriesOptions()
            )
        );

        $this->addColumn(
            'display_in_store',
            array(
                'header' => $helper->__('Display in store'),
                'index' => 'display_in_store',
                'sortable' => false,
                'type' => 'options',
                'options' => $this->_getStoreOptions(),
                'filter_condition_callback' => array($this, 'filterCallback'),
            )
        );

        $this->addColumn(
            'display_in_category',
            array(
                'header' => $helper->__('Display in category'),
                'index' => 'display_in_category',
                'sortable' => false,
                'type' => 'options',
                'options' => $this->_getCategoriesOptions(),
                'filter_condition_callback' => array($this, 'filterCallback'),
            )
        );

        $this->addColumn(
            'frontend_visibility',
            array(
                'header' => $helper->__('Frontend Visibility'),
                'index' => 'frontend_visibility',
                'type' => 'options',
                'options' => array('1' => $helper->__('Yes'), '0' => $helper->__('No'))
            )
        );
        
        $this->addColumn(
            'checkout_visibility',
            array(
                'header' => $helper->__('Checkout Visibility'),
                'index' => 'checkout_visibility',
                'type' => 'options',
                'options' => array('1' => $helper->__('Yes'), '0' => $helper->__('No'))
            )
        );
        
        $this->addColumn(
            'subscribers_count',
            array(
                'header' => $helper->__('Number of subscribers'),
                'index' => 'subscribers_count',
                'type' => 'number',
                'filter_condition_callback' => array($this, 'filterCount'),
            )
        );

        $this->addColumn(
            'display_order',
            array(
                'header' => $helper->__('Display order'),
                'index' => 'display_order',
            )
        );
        return parent::_prepareColumns();
    }

    protected function _getStoreOptions()
    {
        $options = Mage::getModel('adminhtml/system_store')->getStoreOptionHash();
        $options[0] = 'Any';
        return $options;
    }

    protected function _getCategoriesOptions()
    {
        $options = Mage::helper('advancednewsletter')->getCategoriesOptionHash();
        $options[0] = 'Any';
        return $options;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('segment_id');
        $this->getMassactionBlock()->setFormFieldName('segment');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'     => Mage::helper('advancednewsletter')->__('Delete'),
                'url'       => $this->getUrl('*/*/massDelete'),
                'confirm'   => Mage::helper('advancednewsletter')->__('Are you sure?')
            )
        );
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function addAdditionalFields()
    {
        foreach ($this->getCollection() as $item) {
            $item->setData('display_in_store', explode(',', $item->getData('display_in_store')));
            $item->setData('display_in_category', explode(',', $item->getData('display_in_category')));
        }
    }

    protected function filterCallback($collection, $column)
    {
        $val = $column->getFilter()->getValue();
        if (is_null(@$val)) {
            return;
        } else {
            $cond = "FIND_IN_SET('$val', {$column->getIndex()})";
        }
        $collection->getSelect()->where($cond);
    }
    
    protected function filterCount($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $expression = "(SELECT(COUNT(IF(FIND_IN_SET(main_table.code, segments_codes), main_table.segment_id, NULL)))"
            . " from {$collection->getTable('advancednewsletter/subscriber')})"
        ;

        if (isset($value['from']) && isset($value['to'])) {
            $where = "{$expression} >= {$value['from']} AND {$expression} <= {$value['to']}";
        } elseif (isset($value['from']) && !isset($value['to'])) {
            $where = "{$expression} >= {$value['from']}";
        } else {
            $where = "{$expression} <= {$value['to']}";
        }
        $collection->getSelect()->having($where);        
    }

}