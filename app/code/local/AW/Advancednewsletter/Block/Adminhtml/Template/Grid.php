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


class AW_Advancednewsletter_Block_Adminhtml_Template_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    protected function _construct()
    {
        $this->setEmptyText(Mage::helper('advancednewsletter')->__('No Templates Found'));
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceSingleton('advancednewsletter/template_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('advancednewsletter');
        $this->addColumn(
            'template_id',
            array(
                 'header' => $helper->__('ID'),
                'align' => 'center',
                'index' => 'template_id'
            )
        );

        $this->addColumn(
            'template_code',
            array(
                'header' => $helper->__('Template Name'),
                'index' => 'template_code'
            )
        );

        $this->addColumn(
            'added_at',
            array(
                'header' => $helper->__('Date Added'),
                'index' => 'added_at',
                'gmtoffset' => true,
                'type' => 'datetime'
            )
        );

        $this->addColumn(
            'modified_at',
            array(
                'header' => $helper->__('Date Updated'),
                'index' => 'modified_at',
                'gmtoffset' => true,
                'type' => 'datetime'
            )
        );

        $this->addColumn(
            'template_subject',
            array(
                'header' => $helper->__('Subject'),
                'index' => 'template_subject'
            )
        );

        $this->addColumn(
            'template_sender',
            array(
                'header' => $helper->__('Sender'),
                'index' => 'template_sender_email',
                'renderer' => 'adminhtml/newsletter_template_grid_renderer_sender'
            )
        );

        $this->addColumn(
            'template_type',
            array(
                'header' => $helper->__('Template Type'),
                'index' => 'template_type',
                'type' => 'options',
                'options' => array(
                    Mage_Newsletter_Model_Template::TYPE_HTML => 'html',
                    Mage_Newsletter_Model_Template::TYPE_TEXT => 'text'
                ),
            )
        );

        $this->addColumn(
            'smtp_id',
            array(
                'header' => $helper->__('SMTP server'),
                'index' => 'smtp_id',
                'type' => 'options',
                'options' => Mage::getModel('advancednewsletter/smtp')->getSmtpOptionArray(true)
            )
        );

        $this->addColumn(
            'segments_codes',
            array(
                'header' => $helper->__('Segments codes'),
                'index' => 'segments_codes',
                'type' => 'text',
            )
        );

        $this->addColumn(
            'action',
            array(
                'header' => $helper->__('Action'),
                'index' => 'id',
                'sortable' => false,
                'filter' => false,
                'no_link' => true,
                'width' => '170px',
                'renderer' => 'advancednewsletter/adminhtml_template_grid_renderer_action'
            )
        );
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getTemplateId()));
    }

}