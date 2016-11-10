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


class AW_Advancednewsletter_Model_Mysql4_Subscriber extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_write;

    public function _construct()
    {    
        $this->_init('advancednewsletter/subscriber', 'id');
        $this->_write = $this->_getWriteAdapter();
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (is_array($object->getData('segments_codes'))) {
            $object->setData('segments_codes', implode(',', $object->getData('segments_codes')));
        }
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('segments_codes')) {
            $object->setData('segments_codes', explode(',', $object->getData('segments_codes')));
        }
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        if ($object->getData('segments_codes')) {
            if (is_string($object->getData('segments_codes'))) {
                $object->setData('segments_codes', explode(',', $object->getData('segments_codes')));
            }
        } else {
            $object->setData('segments_codes', array());
        }
    }

    public function received(
        AW_Advancednewsletter_Model_Subscriber $subscriber, AW_Advancednewsletter_Model_Queue $queue
    )
    {
        $subscriberLinkTable = Mage::getSingleton('core/resource')->getTableName("advancednewsletter/queue_link");
        $this->_write->beginTransaction();
        try {
            $data['letter_sent_at'] = now();
            $this->_write->update(
                $subscriberLinkTable,
                $data,
                array(
                     $this->_write->quoteInto('subscriber_id=?', $subscriber->getId()),
                    $this->_write->quoteInto('queue_id=?', $queue->getId())
                )
            );
            $this->_write->commit();
        } catch (Exception $e) {
            $this->_write->rollBack();
            Mage::throwException(Mage::helper('newsletter')->__('Cannot mark as received subscriber.'));
        }
        return $this;
    }
}
