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


class AW_Advancednewsletter_Block_Adminhtml_Template_Grid_Renderer_Action
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{

    public function render(Varien_Object $row)
    {
        if ($row->isValidForSend()) {
            $actions[] = array(
                'url' => $this->getUrl('*/awadvancednewsletter_queue/edit', array('template_id' => $row->getId())),
                'caption' => Mage::helper('newsletter')->__('Queue Newsletter...')
            );
        }

        $actions[] = array(
            'url' => $this->getUrl('*/*/preview', array('id' => $row->getId())),
            'popup' => true,
            'caption' => Mage::helper('newsletter')->__('Preview')
        );

        $actions[] = array(
            'url' => $this->getUrl('*/*/sendtest', array('id' => $row->getId())),
            'caption' => Mage::helper('advancednewsletter')->__('Send Test')
        );

        $this->getColumn()->setActions($actions);

        return parent::render($row);
    }

}
