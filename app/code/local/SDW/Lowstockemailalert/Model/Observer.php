<?php

class SDW_Lowstockemailalert_Model_Observer {

	const XML_PATH_EMAIL_TEMPLATE   = 'lowstockemailalert/email/email_template';
	const XML_PATH_EMAIL_SENDER     = 'lowstockemailalert/email/sender_email_identity';
	const XML_PATH_EMAIL_LASTCRON     = 'lowstockemailalert/email/lastcron';

	public function sendMail($schedule) {
		$lastcheck=(int)Mage::getStoreConfig(XML_PATH_EMAIL_LASTCRON);
		Mage::getModel('core/config')->saveConfig(XML_PATH_EMAIL_LASTCRON,$now=time());




		$configManageStock = (int) Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
		$globalNotifyStockQty = (float) Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_NOTIFY_STOCK_QTY);
		
		$collection = Mage::getModel('catalog/product')->getCollection();
		$stockItemTable = $collection->getTable('cataloginventory/stock_item');

		$stockItemWhere = '({{table}}.low_stock_date is not null) '
			. " AND ( ({{table}}.use_config_manage_stock=1 AND {$configManageStock}=1)"
			. " AND {{table}}.qty < "
			. "IF({$stockItemTable}.`use_config_notify_stock_qty`, {$globalNotifyStockQty}, {{table}}.notify_stock_qty)"
			. ' OR ({{table}}.use_config_manage_stock=0 AND {{table}}.manage_stock=1) )';

		$collection
			->addAttributeToSelect('name', true)
			->addAttributeToSelect('fournisseur', true)
			->joinTable('cataloginventory/stock_item', 'product_id=entity_id',
				array(
					'qty'=>'qty',
					'notify_stock_qty'=>'notify_stock_qty',
					'use_config' => 'use_config_notify_stock_qty',
					'low_stock_date' => 'low_stock_date'),
				$stockItemWhere, 'inner')
			->setOrder('low_stock_date');

		$collection->addAttributeToFilter('status',array('in' => Mage::getSingleton('catalog/product_status')->getVisibleStatusIds()));
		Mage::dispatchEvent('rss_catalog_notify_stock_collection_select', array('collection' => $collection));

		$products_html=array();
		foreach($collection as $product)
		{
			$lowstockdate=strtotime($product->getLowStockDate());
			if($lastcheck>$lowstockdate)continue;

			$color=($color=="#F9F9F9")?"#F0F0F0":"#F9F9F9";
			$products_html[]=sprintf("<tr><td style=\"margin:0;padding:5px 10px;background:$color;\">%s</td><td style=\"margin:0;padding:5px 10px;background:$color;\">%s</td><td style=\"margin:0;padding:5px 10px;background:$color;\">%d</td><td style=\"margin:0;padding:5px 10px;background:$color;\">%d</td><td style=\"margin:0;padding:5px 10px;background:$color;\">%s</td></tr>",
				$product->getSku(),
				$product->getName(),
				$product->getQty(),
				$product->getNotifyStockQty(),
				$product->getFournisseur()
			);
		}
		
		if(count($products_html)>0)
		{
			$sender=Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER);

			$mailTemplate = Mage::getModel('core/email_template');
			$mailTemplate->setDesignConfig(array('area' => 'frontend'))
					->sendTransactional( Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
							$sender,
							Mage::getStoreConfig("trans_email/ident_$sender/email"),
							Mage::getStoreConfig("trans_email/ident_$sender/name"),
							array(
								'nbproduct'=>count($products_html),
								'product'=>implode("",$products_html),
								'plural'=>(count($products_html)>1)?"s":""
							)
					);
		}
	}
}