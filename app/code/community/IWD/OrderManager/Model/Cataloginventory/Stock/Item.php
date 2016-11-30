<?php

class IWD_OrderManager_Model_Cataloginventory_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    /**
     * @return int
     */
    public function getStockId()
    {
        return parent::getStockId();
    }

    public function addCatalogInventoryToProductCollection($productCollection)
    {
        $this->_getResource()->addCatalogInventoryToProductCollection($productCollection);
        $productCollection->getSelect()->group('e.entity_id');
        return $this;
    }
}