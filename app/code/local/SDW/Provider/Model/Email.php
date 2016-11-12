<?php
class SDW_Provider_Model_Email extends Mage_Core_Model_Abstract
{
	const XML_PATH_EMAIL_TEMPLATE   = 'provider/email/email_template';
	const XML_PATH_EMAIL_SENDER     = 'provider/email/sender_email_identity';
	 
	public function provider($provider)
	{
		$emailTemplate = Mage::getModel('core/email_template')->setDesignConfig(array('area'=>'frontend'));
		$emailTemplateAdmin = Mage::getModel('core/email_template')->setDesignConfig(array('area'=>'frontend'));
		$sender=Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER);
		
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$providerOrder = $read->fetchAll("SELECT * FROM `mgt_sdw_provider_order` 
														LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
														WHERE `mgt_sdw_provider_order`.status = 'Livraison' 
														AND `mgt_sdw_provider_order`.id_provider=".$provider['id_provider']);
														
														
		$order_id = (int)$provider['provider_order_id'];
		
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		$products_html=array();
		foreach($providerOrder as $product)
		{
			$productDetails = Mage::getModel('catalog/product')->load($product['product_id']);
			
			$color=($color=="#F9F9F9")?"#F0F0F0":"#F9F9F9";
			$products_html[]=sprintf("	<tr>
											<td style=\"margin:0;padding:5px 10px;background:$color;\">%s</td>
											<td style=\"margin:0;padding:5px 10px;background:$color;\">%s</td>
											<td style=\"margin:0;padding:5px 10px;background:$color;\">%d</td>
										</tr>",
				$productDetails->getSku(),
				$productDetails->getName(),
				$product['quantity']
			);
			
			$write->query("UPDATE `mgt_sdw_provider_order` SET status = 'Livré', provider_order_id = ".$order_id." WHERE id_provider_order=".$product['id_provider_order']);
		}

		if(count($products_html)>0)
		{ 
			$pdf = Mage::getModel('provider/pdf_provider')->getPdf($providerOrder,$provider);
			
			//Envois au fournisseur
			
			$emailTemplate->getMail()->createAttachment($pdf->render())->filename =  $provider['code'] . '-' . $provider['sociale'] . '.pdf';
			$emailTemplate->sendTransactional( Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
									$sender,
									$provider['email'],
									$provider['sociale'],
									array(
										'nbproduct'=>count($products_html),
										'product'=>implode("",$products_html),
										'plural'=>(count($products_html)>1)?"s":""
									)
			);
			
			//Envois à l'administrateur
			$emailTemplateAdmin->getMail()->createAttachment($pdf->render())->filename = $provider['code'] . '-' . $provider['sociale'] . '.pdf';
			$emailTemplateAdmin->sendTransactional( Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
									$sender,
									Mage::getStoreConfig("trans_email/ident_$sender/email"),
									Mage::getStoreConfig("trans_email/ident_$sender/name"),
									array(
										'nbproduct'=>count($products_html),
										'product'=>implode("",$products_html),
										'plural'=>(count($products_html)>1)?"s":""
									)
			);

			
			return $emailTemplate;
		}
		
	}
	 
}