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
 * @package    AW_Zblocks
 * @version    2.5.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Adminhtml_Zblocks_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('zblocksGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('zblocks_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('zblocks/zblocks')->getBlockCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();

        foreach($this->getCollection()->getItems() as $item){
            $item->setData('store_ids', @explode(',', $item->getData('store_ids')));//TODO: fix it
        }
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('block_title', array(
                'header' => Mage::helper('zblocks')->__('Title'),
                'align'  => 'left',
                'index'  => 'block_title',
        ));

        $this->addColumn('position_title', array(
                'header'       => Mage::helper('zblocks')->__('Position Title'),
                'align'        => 'left',
                'index'        => 'position',
                'type'         => 'options',
                'filter'       => 'zblocks/adminhtml_widget_grid_column_filter_position',
                'filter_index' => 'main_table.block_position',
                'options'      => Mage::getModel('zblocks/source_position')->toOptionArray(),
                'renderer'     => 'zblocks/adminhtml_widget_grid_column_renderer_position'
        ));

        $this->addColumn('position', array(
                'header'       => Mage::helper('zblocks')->__('Position Code'),
                'align'        => 'left',
                'index'        => 'position',
                'filter_condition_callback' => array($this, '_addPositionCodeFilterCallback'),
        ));

        $this->addColumn('block_count', array(
                'header'                    => Mage::helper('zblocks')->__('Blocks'),
                'index'                     => 'block_count',
                'align'                     => 'right',
                'width'                     => '50px',
                'filter_condition_callback' => array($this, '_filterBlockCount'),
        ));

        $this->addColumn('rotator_mode', array(
                'header'  => Mage::helper('zblocks')->__('Display Items'),
                'index'   => 'rotator_mode',
                'type'    => 'options',
                'align'   => 'left',
                'width'   => '80px',
                'options' => Mage::helper('zblocks')->getRotatorModesToOptionsArray(),
        ));

        $this->addColumn('representation_mode', array(
                'header'  => Mage::helper('zblocks')->__('Representation Mode'),
                'index'   => 'representation_mode',
                'type'    => 'options',
                'align'   => 'left',
                'width'   => '80px',
                'options' => Mage::helper('zblocks')->getRepresentationModeToOptionsArray(),
            ));

        $this->addColumn('schedule_from_date', array(
                'header' => Mage::helper('zblocks')->__('Date From'),
                'index'  => 'schedule_from_date',
                'type'   => 'date',
                'width'  => '100px',
        ));

        $this->addColumn('schedule_to_date', array(
                'header' => Mage::helper('zblocks')->__('Date To'),
                'index'  => 'schedule_to_date',
                'type'   => 'date',
                'width'  => '100px',
        ));

        $this->addColumn('schedule_pattern', array(
                'header'  => Mage::helper('zblocks')->__('Schedule pattern'),
                'index'   => 'schedule_pattern',
                'type'    => 'options',
                'align'   => 'left',
                'options' => Mage::helper('zblocks')->getPatternsToOptionsArray(),
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_ids', array(
                'header'                    => Mage::helper('zblocks')->__('Store View'),
                'index'                     => 'store_ids',
                'type'                      => 'store',
                'store_all'                 => true,
                'store_view'                => true,
                'sortable'                  => false,
                'renderer'                  => 'Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Store',
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('block_is_active', array(
                'header'  => Mage::helper('zblocks')->__('Status'),
                'index'   => 'block_is_active',
                'align'   => 'left',
                'width'   => '80px',
                'type'    => 'options',
                'options' => array(
                    0 => Mage::helper('zblocks')->__('Disabled'),
                    1 => Mage::helper('zblocks')->__('Enabled')
                ),
        ));

        $this->addColumn('block_sort_order', array(
                'header' => Mage::helper('zblocks')->__('Sort Order'),
                'align'  => 'right',
                'index'  => 'block_sort_order',
                'width'  => '80px',
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('zblocks')->__('Action'),
                'width'     => '80',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('zblocks')->__('Delete'),
                        'url'       => array('base'=> '*/*/delete'),
                        'field'     => 'id',
                        'confirm'   => Mage::helper('zblocks')->__('Are you sure you want to delete the block?'),
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->getSelect()->where('(find_in_set(?, store_ids) OR find_in_set(0, store_ids))', $value);
    }

    protected function _filterBlockCount($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->getSelect()->where('block_count=?', $value);
    }

    protected function _addPositionCodeFilterCallback($collection, $column)
    {
        if (!$condition = $column->getFilter()->getCondition()) {
            return;
        }
        $collection->addFieldToFilter(
            $collection->getConnection()->getCheckSql(
                "main_table.block_position = '" . AW_Zblocks_Model_Source_Position::CUSTOM_POSITION . "'",
                "CONCAT(main_table.block_position_custom, ' *')",
                "main_table.block_position"
            ),
            $condition
        );
    }
}
