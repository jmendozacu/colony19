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


class AW_Advancednewsletter_Block_Adminhtml_Automanagement_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('rule_id');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('advancednewsletter/automanagement')->getResourceCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'rule_id',
            array(
                'header' => Mage::helper('advancednewsletter')->__('ID'),
                'align' => 'right',
                'width' => '50px',
                'index' => 'rule_id',
            )
        );

        $this->addColumn(
            'title',
            array(
                'header' => Mage::helper('advancednewsletter')->__('Title'),
                'align' => 'left',
                'index' => 'title',
            )
        );

        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('advancednewsletter')->__('Status'),
                'align' => 'left',
                'width' => '100px',
                'index' => 'status',
                'type' => 'options',
                'options' => array(
                    1 => 'Active',
                    0 => 'Inactive',
                ),
            )
        );
        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getRuleId()));
    }

}
