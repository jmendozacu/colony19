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


class AW_Advancednewsletter_Block_Adminhtml_Customer_Edit_Tab_Newsletter
    extends Mage_Adminhtml_Block_Widget_Form
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('advancednewsletter/customer/tab/newsletter.phtml');
    }

    public function initForm()
    {
        $customer = Mage::registry('current_customer');
        $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($customer->getEmail());
        Mage::register('subscriber', $subscriber);
        return $this;
    }

    protected function _prepareLayout()
    {
        $gridBlock = $this->getLayout()->createBlock(
            'advancednewsletter/adminhtml_customer_edit_tab_newsletter_grid', 'advancednewsletter.grid'
        );
        $this->setChild('grid', $gridBlock);
        return parent::_prepareLayout();
    }

    public function getSegments()
    {
        return Mage::getModel('advancednewsletter/segment')->getCollection();
    }

    public function isChecked($segment)
    {
        return in_array($segment->getCode(), $this->getSubscriber()->getSegmentsCodes());
    }

    public function getSubscriber()
    {
        if (!Mage::registry('subscriber')) {
            $customer = Mage::registry('current_customer');
            $subscriber = Mage::getModel('advancednewsletter/subscriber')->loadByEmail($customer->getEmail());
            Mage::register('subscriber', $subscriber);
        }
        return Mage::registry('subscriber');
    }

}
