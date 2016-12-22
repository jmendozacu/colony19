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


class AW_Advancednewsletter_Helper_AnVersionExport extends Mage_Core_Helper_Abstract
{

    public function exportStart()
    {
        $this->exportSubscribers();
        $this->exportTemplates();
        $this->exportAutomanagementRules();
        $this->exportSegments();
    }

    protected function exportSubscribers()
    {
        $resource = Mage::getSingleton('core/resource');
        $connWrite = $resource->getConnection('log_write');
        $subscriberTable = $resource->getTableName("advancednewsletter/subscriber");
        foreach (Mage::getModel('advancednewsletter/subscriptions')->getCollection() as $anOldSubscriber) {
            $newsletterSubscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($anOldSubscriber->getEmail());
            if (!$newsletterSubscriber->getId())
                continue;
            try {
                $newStatus = 0;
                switch ($newsletterSubscriber->getStatus()) {
                    case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                        $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_SUBSCRIBED;
                        break;
                    case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
                        $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_NOTACTIVE;
                        break;
                    case Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                        $newStatus = AW_Advancednewsletter_Model_Subscriber::STATUS_UNSUBSCRIBED;
                        break;
                }

                $oldSubscriberSegments = $anOldSubscriber->getData('segments_codes');
                if ($oldSubscriberSegments) {
                    $oldSubscriberSegments = explode(',', $oldSubscriberSegments);
                } else {
                    $oldSubscriberSegments = array();
                }
                if (in_array(AW_Advancednewsletter_Helper_Data::AN_SEGMENTS_ALL, $oldSubscriberSegments)) {
                    $segmentsOptionArray = Mage::getModel('advancednewsletter/segment')->getSegmentOptionArray();
                    $subscriberSegments = implode(',', array_keys($segmentsOptionArray));
                } else {
                    $subscriberSegments = $anOldSubscriber->getData('segments_codes');
                }



                $data = array();
                $data['store_id'] = $newsletterSubscriber->getStoreId();
                if ($newsletterSubscriber->getCustomerId())
                    $data['customer_id'] = $newsletterSubscriber->getCustomerId();
                $data['email'] = $newsletterSubscriber->getSubscriberEmail();
                $data['status'] = $newStatus;
                $data['first_name'] = $anOldSubscriber->getFirstName();
                $data['last_name'] = $anOldSubscriber->getLastName();
                $data['phone'] = $anOldSubscriber->getPhone();
                $data['salutation'] = $anOldSubscriber->getSalutation();
                $data['confirm_code'] = $newsletterSubscriber->getSubscriberConfirmCode();
                $data['segments_codes'] = $subscriberSegments;

                $sql = sprintf(
                    "INSERT IGNORE INTO %s (%s) VALUES('%s')",
                    $subscriberTable,
                    implode(',', array_keys($data)),
                    implode("','", $data)
                );

                $connWrite->query($sql);
            } catch (Exception $ex) {
                
            }
        }
    }

    protected function exportTemplates()
    {
        foreach (Mage::getModel('advancednewsletter/templates')->getCollection() as $anOldTemplate) {
            $newsletterTemplate = Mage::getModel('newsletter/template')->load($anOldTemplate->getTemplateId());
            if (!$newsletterTemplate)
                continue;
            $segmentsCodes = array();
            $segmentsIds = explode(',', $anOldTemplate->getSegmentsIds());
            foreach ($segmentsIds as $segmentId) {
                if ($segmentCode = Mage::getModel('advancednewsletter/segment')->load($segmentId)->getCode())
                    $segmentsCodes[] = $segmentCode;
            }
            try {
                Mage::getModel('advancednewsletter/template')
                        ->addData($newsletterTemplate->getData())
                        ->setId(null)
                        ->setSegmentsCodes($segmentsCodes)
                        ->setSmtpId($anOldTemplate->getSmtpId())
                        ->save();
            } catch (Exception $ex) {
                
            }
        }
    }

    protected function exportAutomanagementRules()
    {
        foreach (Mage::getModel('advancednewsletter/automanagement')->getCollection() as $rule) {
            $upgradeRule = Mage::getModel('advancednewsletter/automanagement')->load($rule->getId());
            Mage::getModel('advancednewsletter/automanagement')
                    ->load($rule->getId())
                    ->setConditions($upgradeRule->getConditions())
                    ->setSegmentsCut($this->exportRuleLogic($rule->getSegmentsCut()))
                    ->setSegmentsPaste($this->exportRuleLogic($rule->getSegmentsPaste()))
                    ->save();
        }
    }

    protected function exportRuleLogic($segmentsString)
    {
        $segments = explode(';', $segmentsString);
        $newSegments = array();
        foreach ($segments as $segment) {
            if ($segment == AW_Advancednewsletter_Helper_Data::RULES_NO_CHANGE) {
                $newSegments = array();
                break;
            }
            if ($segment == AW_Advancednewsletter_Helper_Data::AN_SEGMENTS_ALL) {
                $newSegments = array();
                foreach (Mage::getModel('advancednewsletter/segment')->getCollection() as $tmpSegment) {
                    $newSegments[] = $tmpSegment->getCode();
                }
                break;
            }
            $newSegments[] = $segment;
        }
        return $newSegments;
    }

    protected function exportSegments()
    {
        foreach (Mage::getModel('advancednewsletter/segment')->getCollection() as $segment) {
            $segment->setFrontendVisibility(1)->save();
        }
    }

}