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


class AW_Zblocks_Adminhtml_Zblocks_ZblocksController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('admin/cms/zblocks');
    }

    protected function _setTitle($title)
    {
        if (Mage::helper('zblocks')->checkExtensionVersion('Mage_Core', '0.8.25')) {
            $this->_title($title);
        }
        return $this;
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('cms/zblocks')
            ->_addBreadcrumb($this->__('Z-Blocks'), $this->__('Block Manager'));

        $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);

        return $this;
    }

    public function indexAction()
    {
        $this->_setTitle($this->__('Z-Blocks'))
            ->_setTitle($this->__('Blocks List'));
        $this->_initAction()->renderLayout();
    }

    public function editAction()
    {
        $this->_setTitle($this->__('Z-Blocks'))
            ->_setTitle($this->__('Edit Block'));
        $session = Mage::getSingleton('adminhtml/session');
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('zblocks/zblocks')->load($id);

        if ($model->getId() || !$id) {
            $data = $model->getData();

            //set store_ids data for single store
            if (Mage::app()->isSingleStoreMode()) {
                $defaultStoreView = Mage::app()->getDefaultStoreView();

                if (!is_null($defaultStoreView)) {
                    $storeView = $defaultStoreView->getId();
                } else {
                    $storeView = Mage::app()->getStore()->getId();
                }
                $data['store_ids'] = $storeView;
            }

            $sessionData = $session->getZBlocksData(true);
            if (is_array($sessionData)) {
                unset($sessionData['store_ids']);
                $data = array_merge($data, $sessionData);
            }
            $session->setZBlocksData(false);
            
            Mage::register('zblocks_data', $data);

            $this->loadLayout();
            $this->_setActiveMenu('cms/zblocks');

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit'))
                ->_addLeft($this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tabs'));

            $this->renderLayout();
        } else {
            $session->addError($this->__('The block does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('zblocks/condition'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }    
    
    /*
     * Parses date and time
     * @return int Parsing errors count
     */
    private function _timeParseErrors($time)
    {
        $data = date_parse($time);
        return $data['error_count'];
    }

    public function saveAction()
    {
        $session = Mage::getSingleton('adminhtml/session');
        $session->setZBlocksData(false);

        if ($data = $this->getRequest()->getPost()) {
            $model = Mage::getModel('zblocks/zblocks');
            $id = $this->getRequest()->getParam('id');

            if (!isset($data['category_ids'])
                && $id
            ) $oldData = $model->load($id)->getData(); // to get category_ids from the table
            else $oldData = array();

            $data = array_merge($oldData, $data);
            
            $model->setData($data)->setId($id);
            try
            {
                if ($model->getBlockPosition() == 'custom' && !$model->getBlockPositionCustom()) {
                    $session->addError($this->__('Please set block Custom Position identifier'));
                    $session->setZBlocksData($data);
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }

                $categoryIds = array_unique(explode(',', $model->getCategoryIds()));
                foreach ($categoryIds as $key => $value) {
                    if (!$value) {
                        unset($categoryIds[$key]);
                    }
                }
                $model->setCategoryIds(implode(',', $categoryIds));
                $model->setStoreIds(implode(',', $model->getStoreIds()));
                if ($model->getCreationTime == NULL) {
                    $model->setCreationTime(now());
                }
                $model->setUpdateTime(now());

                // check if schedule date was entered correctly
                $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                if ($date = $model->getScheduleFromDate()) {
                    $date = Mage::app()->getLocale()->date($date, $format, null, false);
                    $model->setScheduleFromDate($date->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
                } else {
                    $model->setScheduleFromDate(null);
                }

                if ($date = $model->getScheduleToDate()) {
                    $date = Mage::app()->getLocale()->date($date, $format, null, false);
                    $model->setScheduleToDate($date->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
                } else {
                    $model->setScheduleToDate(null);
                }

                (!is_null($model->getScheduleFromDate()) && !is_null($model->getScheduleToDate()) &&
                    strtotime($model->getScheduleFromDate()) > strtotime($model->getScheduleToDate()))
                    ? $dateError = $this->__('Start date can\'t be greater than end date')
                    : $dateError = false;

                if (is_null($model->getScheduleFromDate())) {
                    $model->setScheduleFromDate(new Zend_Db_Expr('null'));
                }
                if (is_null($model->getScheduleToDate())) {
                    $model->setScheduleToDate(new Zend_Db_Expr('null'));
                }


                // check if schedule time was entered correctly
                if ($data['schedule_from_time'] && $this->_timeParseErrors($data['schedule_from_time'])) {
                    $parseError = $this->__('Schedule From Time');
                } elseif ($data['schedule_to_time'] && $this->_timeParseErrors($data['schedule_to_time'])) {
                    $parseError = $this->__('Schedule To Time');
                } else {
                    $parseError = false;
                }

                $timeError = false;
                if (!is_null($data['schedule_from_time']) && !is_null($data['schedule_to_time'])
                    && strtotime($data['schedule_to_time']) < strtotime($data['schedule_from_time'])
                ) {
                    $timeError = $this->__('Start time can\'t be greater than end time');
                }

                if ($parseError || $dateError || $timeError) {
                    if ($parseError) {
                        $session->addError($this->__('Error in "%s" field', $parseError));
                    }
                    if ($dateError) {
                        $session->addError($dateError);
                    }
                    if ($timeError) {
                        $session->addError($timeError);
                    }
                    $session->setZBlocksData($model->getData());
                    $this->_redirect('*/*/edit', array('id' => $model->getId(), 'tab' => 'schedule'));
                    return;
                }

                $model->addCustomerGroups(@$data['customer_group']); 
                $model->save();

                //re-saving content items with parent settings
                $contentCollection = Mage::getModel('zblocks/content')->getCollection();
                $contentCollection->addFieldToFilter('zblock_id', array('eq' => $model->getId()));
                $contentCollection->load();

                foreach ($contentCollection->getItems() as $item) {
                    if ($item->getStoreIds() == $model->getStoreIds()
                        && $item->getCustomerGroup() == $model->getCustomerGroup()
                        && $item->getMssRuleId() == $model->getMssRuleId()
                    ) {
                        continue;
                    }
                    $item->load($item->getId());
                    $item->setStoreIds($item->getStoreIds());
                    $item->setCustomerGroup($item->getCustomerGroup());
                    $item->save();
                }

                $conditionModel = Mage::getModel('zblocks/condition');
                $id = $model->getId();
                $request = $this->getRequest();
                $conditionModel->load($id,'zblock_id');

                $condition = $request->getPost();
                $condition['conditions'] = $condition['rule']['conditions'];
                unset($condition['rule']);
                
                $conditionModel->loadPost($condition);
                if ($id) {
                    $conditionModel->setZblockId($id);
                }
                $conditionModel->save();

                if ($this->getRequest()->getParam('duplicate')) {
                    return $this->_redirect(
                        '*/*/duplicate',
                        array(
                            'id' => $id
                        )
                    );
                }

                $session->addSuccess($this->__('Block %s was successfully saved', '&quot;' . $model->getBlockTitle() . '&quot;'));

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                $session->addError($e->getMessage());
                $session->setZBlocksData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $session->addError($this->__('Unable to find a block to save'));
        $this->_redirect('*/*/');
    }

    public function duplicateAction()
    {
        if ($this->getRequest()->getParam('id')) {
            $originalZblockId = $this->getRequest()->getParam('id');

            //import zblock
            $newZblock = Mage::getModel('zblocks/zblocks');
            $newZblock->load($originalZblockId);
            $newZblock->setData('zblock_id', null);
            $newZblock->setData('block_is_active', false);
            $newZblock->save();
            $newZblock->setData(
                'block_title', AW_Zblocks_Helper_Data::FORM_DUPLICATE_NAME . $newZblock->getBlockTitle()
            );
            $newZblock->save();

            //import zblock's conditions
            $conditionModel = Mage::getModel('zblocks/condition')->load($originalZblockId, 'zblock_id');
            $conditionModel->setData('rule_id', null);
            $conditionModel->setData('zblock_id', $newZblock->getId());
            $conditionModel->save();

            //import zblock's items
            $itemsCollection = Mage::getResourceModel('zblocks/content_collection');
            $itemsCollection->addFieldToFilter('zblock_id', array('eq' => $originalZblockId));
            $itemsCollection->load();

            foreach ($itemsCollection->getItems() as $contentItem) {
                $contentItem->load($contentItem->getId());
                $contentItem->setData('block_id', null);
                $contentItem->setData('zblock_id', $newZblock->getId());

                $contentItem->save();
            }

            $this->_getSession()->addSuccess($this->__('Block successfully saved and duplicated'));
            return $this->_redirect(
                '*/*/edit',
                array(
                    'id' => $newZblock->getId(),
                )
            );
        }
        return $this->_redirect('*/*/list');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('zblocks/zblocks')->load($id);
                $title = $model->getBlockTitle();
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Block %s was successfully deleted', '&quot;' . $title . '&quot;'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

// = = = = = = = = = = = = = = = = = = = = = content actions section = = = = = = = = = = = = = = = = = = = =

    public function editContentAction()
    {
        $this->_setTitle($this->__('Z-Blocks'))
            ->_setTitle($this->__('Edit Block Content'));
        $session = Mage::getSingleton('adminhtml/session');
        if ($blockId = $this->getRequest()->getParam('block_id')) {
            $zblock = Mage::getModel('zblocks/zblocks')->load($blockId);
            if ($zblock->getRepresentationMode() == AW_Zblocks_Helper_Data::REPRESENTATION_MODE_SLIDER) {
                $session->addNotice($this->__('Z-Block height equals max height amongst all enabled content items.'));
            }

        }

        $model = Mage::getModel('zblocks/content');
        if ($id = $this->getRequest()->getParam('id')) {
            $model->load($id);
            if (!$model->getId()) {
                $session->addError($this->__('This content block no longer exists'));
                $this->_redirectReferer();
                return;
            }
        }

        $data = $model->getData();
        $sessionData = $session->getZBlocksContentData(true);
        if (is_array($sessionData)) {
            $data = array_merge($data, $sessionData);
        }
        $session->setZBlocksContentData(false);

        if ($blockId = $this->getRequest()->getParam('block_id')) {
            $data['zblock_id'] = $blockId;
        }
        Mage::register('zblocks_content', $data);
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_content_edit')->setData('action', $this->getUrl('*/*/saveContent')))
            ->renderLayout();
    }

    public function saveContentAction()
    {
        $session = Mage::getSingleton('adminhtml/session');

        if ($data = $this->getRequest()->getPost()) {
            $blockModel = Mage::getModel('zblocks/zblocks')->load($data['zblock_id']);
            if (!$blockModel->getId()) {
                $session->addError($this->__('Parent block no longer exists'));
                $this->_redirect('*/*/');
                return;
            }

            $model = Mage::getModel('zblocks/content');
            try {
                $model
                    ->setData($data)
                    ->setId($this->getRequest()->getParam('id'))
                    ->save();

                $session->addSuccess($this->__('Block %s was successfully saved', '&quot;' . $model->getTitle() . '&quot;'));

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/editContent', array('id' => $model->getId(), 'block_id' => $data['zblock_id']));
                } else {
                    $this->_redirect('*/*/edit', array('id' => $data['zblock_id'], 'tab' => 'content'));
                }
                return;
            } catch (Exception $e) {
                $session->addError($e->getMessage());
                $session->setZBlocksContentData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $session->addError($this->__('Unable to find a block to save'));
        $this->_redirect('*/*/');
    }

    public function deleteContentAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('zblocks/content')->load($id);

                $title = $model->getTitle();
                $zblockId = $model->getZblockId();

                $model->setId($id)->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Block "%s" was successfully deleted', $title));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        if (isset($zblockId)) {
            $this->_redirect('*/*/edit', array('id' => $zblockId, 'tab' => 'content'));
        } else {
            $this->_redirect('*/*/');
        }
    }

    public function contentMassStatusAction()
    {
        $blockIds = $this->getRequest()->getParam('block_id', null);
        $status = $this->getRequest()->getParam('status', null);
        try {
            if (!is_array($blockIds)) {
                throw new Mage_Core_Exception($this->__('Invalid content block id(s)'));
            }

            if (null === $status) {
                throw new Mage_Core_Exception($this->__('Invalid status value'));
            }
            foreach ($blockIds as $id) {
                Mage::getSingleton('zblocks/content')
                    ->load($id)
                    ->setIsActive($status)
                    ->save()
                ;
            }
            if (count($blockIds) == 1) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('%d content block has been updated successfully', count($blockIds))
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('%d content blocks have been updated successfully', count($blockIds))
                );
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $zblockId = $this->getRequest()->getParam('id', null);
        $this->_redirect('*/*/edit', array('id' => $zblockId, 'tab' => 'content'));
    }

    public function contentMassDeleteAction()
    {
        $blockIds = $this->getRequest()->getParam('block_id', null);
        try {
            if (!is_array($blockIds)) {
                throw new Mage_Core_Exception($this->__('Invalid content block id(s)'));
            }

            foreach ($blockIds as $id) {
                Mage::getSingleton('zblocks/content')
                    ->load($id)
                    ->delete()
                ;
            }
            if (count($blockIds) == 1) {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('%d content block has been deleted successfully', count($blockIds))
                );
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('%d content blocks have been deleted successfully', count($blockIds))
                );
            }

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $zblockId = $this->getRequest()->getParam('id', null);
        $this->_redirect('*/*/edit', array('id' => $zblockId, 'tab' => 'content'));
    }


// = = = = = = = = = = = = = = = = = = = = = AJAX section = = = = = = = = = = = = = = = = = = = =

    /*
     * Dynamic grin renewal through AJAX query action
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_grid')->toHtml()
        );
    }

    /*
     * Content grid action
     */
    public function editGridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_content_grid')->toHtml()
        );
    }

    /*
     * Category children action
     */
    public function categoriesJsonAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('zblocks/adminhtml_zblocks_edit_tab_conditions_categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }
}
