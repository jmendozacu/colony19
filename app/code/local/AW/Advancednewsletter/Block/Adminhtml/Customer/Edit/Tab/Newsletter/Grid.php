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


class AW_Advancednewsletter_Block_Adminhtml_Customer_Edit_Tab_Newsletter_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('queueGrid');
        $this->setDefaultSort('start_at');
        $this->setDefaultDir('desc');

        $this->setUseAjax(true);

        $this->setEmptyText(Mage::helper('customer')->__('No Newsletter Found'));
    }

    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/awadvancednewsletter_customer/newsletter', array('_current' => true));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('advancednewsletter/queue_collection')
                ->addTemplateInfo()
                ->addSubscriberFilter(Mage::registry('subscriber')->getId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'queue_id',
            array(
                'header' => Mage::helper('customer')->__('ID'),
                'align' => 'left',
                'index' => 'queue_id',
                'width' => 10
            )
        );

        $this->addColumn(
            'start_at',
            array(
                'header' => Mage::helper('customer')->__('Newsletter Start'),
                'type' => 'datetime',
                'align' => 'center',
                'index' => 'queue_start_at',
                'default' => ' ---- '
            )
        );

        $this->addColumn(
            'finish_at',
            array(
                'header' => Mage::helper('customer')->__('Newsletter Finish'),
                'type' => 'datetime',
                'align' => 'center',
                'index' => 'queue_finish_at',
                'gmtoffset' => true,
                'default' => ' ---- '
            )
        );

        $this->addColumn(
            'letter_sent_at',
            array(
                'header' => Mage::helper('customer')->__('Newsletter Received'),
                'type' => 'datetime',
                'align' => 'center',
                'index' => 'letter_sent_at',
                'gmtoffset' => true,
                'default' => ' ---- '
            )
        );

        $this->addColumn(
            'template_subject',
            array(
                'header' => Mage::helper('customer')->__('Subject'),
                'align' => 'center',
                'index' => 'template_subject'
            )
        );

        $this->addColumn(
            'status',
            array(
                'header' => Mage::helper('customer')->__('Status'),
                'align' => 'center',
                'filter' => 'advancednewsletter/adminhtml_customer_edit_tab_newsletter_grid_filter_status',
                'index' => 'queue_status',
                'renderer' => 'advancednewsletter/adminhtml_customer_edit_tab_newsletter_grid_renderer_status'
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => Mage::helper('customer')->__('Action'),
                'align' => 'center',
                'filter' => false,
                'sortable' => false,
                'renderer' => 'advancednewsletter/adminhtml_customer_edit_tab_newsletter_grid_renderer_action'
            )
        );
        return parent::_prepareColumns();
    }

}
