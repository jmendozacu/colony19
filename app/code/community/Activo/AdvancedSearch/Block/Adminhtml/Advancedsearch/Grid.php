<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

class Activo_AdvancedSearch_Block_Adminhtml_Advancedsearch_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize grid settings
     *
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('advancedsearch');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Prepare codes collection
     *
     * @return Activo_BulkImages_Block_Adminhtml_BulkImages_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('advancedsearch/dictionary_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Configuration of grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'        =>  Mage::helper('advancedsearch')->__('ID'),
            'align'         =>  'right',
            'width'         =>  '50px',
            'filter_index'  =>  'id',
            'index'         =>  'id',
        ));

        $this->addColumn('num_products', array(
            'header'        =>  Mage::helper('advancedsearch')->__('Number of Products'),
            'align'         =>  'right',
            'width'         =>  '70px',
            'filter_index'  =>  'num_products',
            'index'         =>  'num_products',
        ));

        $this->addColumn('num_words', array(
            'header'        =>  Mage::helper('advancedsearch')->__('Number of Words'),
            'align'         =>  'right',
            'width'         =>  '70px',
            'filter_index'  =>  'num_words',
            'index'         =>  'num_words',
        ));
        
        $this->addColumn('created_at', array(
            'header'        =>  Mage::helper('advancedsearch')->__('Created At'),
            'width'         =>  '160px',
            'index'         =>  'created_at',
            'type'          =>  'datetime',
        ));
        
        $this->addColumn('modified_at', array(
            'header'        =>  Mage::helper('advancedsearch')->__('Modified At'),
            'width'         =>  '160px',
            'index'         =>  'modified_at',
            'type'          =>  'datetime',
        ));
        
        return parent::_prepareColumns();
    }
}
