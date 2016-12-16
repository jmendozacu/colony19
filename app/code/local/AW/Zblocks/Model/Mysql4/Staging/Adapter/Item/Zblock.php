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


class AW_Zblocks_Model_Mysql4_Staging_Adapter_Item_Zblock extends Enterprise_Staging_Model_Resource_Adapter_Item_Default
{
    /**
     * Create item table, run website and item table structure
     *
     * @param string $entityName
     * @param mixed $fields
     * @return AW_Zblocks_Model_Mysql4_Staging_Adapter_Item_Zblock
     * 
     */
    protected function _createStoreScopeItemTableData($entityName, $fields)
    {
        return $this;
    }
    
    protected function _mergeTableDataInStoreScope($entityName, $fields)
    {
        $staging      = $this->getStaging();
        $storesMap    = $staging->getMapperInstance()->getStores();

        if (!empty($storesMap)) {

            foreach ($storesMap as $stagingStoreId => $masterStoreIds) {
                $stagingStoreId = intval($stagingStoreId);

                foreach ($masterStoreIds as $masterStoreId) {
                    $masterStoreId = intval($masterStoreId);

                    $this->_beforeStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId);

                    if ($entityName == 'zblocks/zblocks') {
                        $zblocksCollection = Mage::getModel('zblocks/zblocks')->getCollection()
                                    ->addStoreFilter($stagingStoreId)
                                    ->addExcludeStoreFilter($masterStoreId);
                        foreach ($zblocksCollection as $zblock) {
                            $_zblockStores = explode(',', $zblock->getData('store_ids'));
                            array_push($_zblockStores, $masterStoreId);
                            $_zblockStores = implode(',', $_zblockStores);
                            $zblock
                                ->setData('store_ids', $_zblockStores)
                                ->save();
                                
                            $contentColl = Mage::getModel('zblocks/content')->getCollection();
                            $contentColl->addFieldToFilter('zblock_id', array('eq' => $zblock->getZblockId()));
                            foreach ($contentColl as $_content) {
                                $_content->addData(unserialize($_content->getAdditionalParamsSerialized()));
                                if ($_content->getUseParentStoreIds()) {
                                        $_content->save();
                                } else {
                                    $_contentStores = explode(',', $_content->getData('store_ids'));
                                    if (is_array($_contentStores) && !in_array($masterStoreId, $_contentStores)) {
                                        array_push($_contentStores, $masterStoreId);
                                    }
                                    $_contentStores = implode(',', $_contentStores);
                                    $_content
                                        ->setData('store_ids', $_contentStores)
                                        ->save();
                                }
                            }
                        }
                    }

                    $this->_afterStoreMerge($entityName, $fields, $masterStoreId, $stagingStoreId);
                }
            }
        }
        return $this;
    }
}