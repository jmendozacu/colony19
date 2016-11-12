<?php
set_time_limit(0);


class SDW_Export_Model_Observer
{
	private $EXPORT_HOST;
	private $EXPORT_LOGIN;
	private $EXPORT_PASS;
	private $EXPORT_DIR;

	public function __construct()
	{
		if ($_SERVER['HTTP_HOST'] === 'colony.sentinellesduweb.net') {
			$this->EXPORT_HOST  = "46.18.192.100";
			$this->EXPORT_LOGIN = "1664_depottest";
			$this->EXPORT_PASS  = "depottest";
			$this->EXPORT_DIR   = "/colony/";
		} else {
			$this->EXPORT_HOST  = "ftp.alpha-d-s.com";
			$this->EXPORT_LOGIN = "powell";
			$this->EXPORT_PASS  = "POW591adsn";
			$this->EXPORT_DIR   = "/POWELL/POWELL_Vers_ADSN/";
		}
	}

	private function cleanCut($string,$length,$cutString = '...')
	{
		$string = utf8_decode($string);
		$string = str_replace("&nbsp;", " ", $string);
		$string = html_entity_decode($string);
		$string = strip_tags($string);
		$string = trim(preg_replace('/\s\s+/', ' ', $string));
		$string = str_replace('’','\'',$string);
        $string = str_replace("&", " ", $string);
		if(strlen($string) <= $length)
		{
			return $string;
		}
		$str = substr($string,0,$length-strlen($cutString)+1);
		return substr($str,0,strrpos($str,' ')).$cutString;
	}

	private function cleanAdresse($string)
	{
		$string = str_replace(CHR(13).CHR(10)," ",$string);
		$string = str_replace("\r\n"," ",$string);
		$string = str_replace("\n"," ",$string);

		return $string;
	}

	/**Gestion Ecriture FTP**/
	private function uploadFtp($nom_fichier_local, $nom_fichier_distant) {
		$conn_id = ftp_connect($this->EXPORT_HOST);
		$login_result = ftp_login($conn_id, $this->EXPORT_LOGIN, $this->EXPORT_PASS);
		ftp_pasv($conn_id, false);
		if (ftp_put($conn_id, $this->EXPORT_DIR.$nom_fichier_distant, $nom_fichier_local,  FTP_BINARY)) {
			echo "Le fichier $nom_fichier_distant a été chargé avec succès\n";
		}
		else {
			echo "Il y a eu un problème lors du chargement du fichier $nom_fichier_distant\n";
		}
		ftp_close($conn_id);
	}

	public function articlesExport() {
		try {
			$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
			$collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
            $collection->joinField('manages_stock','cataloginventory/stock_item','use_config_manage_stock','product_id=entity_id');

			$_xml= '<?xml version="1.0"?>
';
			foreach($collection as $product){
				$productData = $product->getData();

				$categories = $product->getCategoryIds();
				foreach ($categories as $catid) {
					$nameCategory = $this->cleanCut(Mage::getModel('catalog/category')->load($catid)->getName(),30,'');
				}

				$number = false;
                $colis = explode("\n",$product->shipping_sku);
                array_walk($colis,function(&$valeur){
                    $valeur=trim($valeur);
                });
                $colis = array_filter($colis);
				$type = 'PRO';
				if(count($colis) > 1) {
					$number = true;
					$type = 'KIT';
				}
				$i = 1;

				/*
				$_xml .= '<PRODUIT>';
					$_xml .= '<CODE_ART>'.$product->entity_id.'</CODE_ART>';
					$_xml .= '<REFERENCE_ART>'.trim($product->getSku()).'</REFERENCE_ART>';
					$_xml .= '<LIB_LON>'.$this->cleanCut($product->getDescription(),70,'').'</LIB_LON>';
					$_xml .= '<LIB_COURT>'.$this->cleanCut($product->getName(),70,'').'</LIB_COURT>';
					$_xml .= '<TYPE_ART>'.$type.'</TYPE_ART>';
					$_xml .= '<ART_PHYSIQUE>'.(($product->getTypeID() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL)? 'N':'O').'</ART_PHYSIQUE>';
					$_xml .= '<FAMILLE>'.$nameCategory.'</FAMILLE>';
					$_xml .= '<STOCK>'.number_format($product->getQty(),0,'.','').'</STOCK>';
					$_xml .= '<PRICEHT>'.$product->getPrice().'</PRICEHT>';
					$_xml .= '<POIDS>'.number_format($product->getWeight() * 1000,0,'.','').'</POIDS>';
					$_xml .= '<CROSSDOCK>'.$product->getManages_stock().'</CROSSDOCK>';
					$_xml .= '<COLIS>';

						foreach($colis as $coli){
							$_xml .= '<COLIS_DESIGNATION>'.$this->cleanCut($product->getName(),70,'');
								if($number)$_xml .= '-'.$i;
							$_xml .= '</COLIS_DESIGNATION>';
								$_xml .= '<COLIS_REFERENCE>'.trim($coli).'</COLIS_REFERENCE>';
							$i++;
						}

					$_xml .= '</COLIS>';

				$_xml .= '</PRODUIT>
';				*/

				$_xml .= '<PRODUIT>';
                    $_xml .= '<CODE_ART>'.$product->entity_id.'</CODE_ART>';
                    $_xml .= '<EAN>'.$product->getCodeBarre().'</EAN>';
					$_xml .= '<REFERENCE_ART>'.$this->cleanCut($product->getSku(),18,'').'</REFERENCE_ART>';
					$_xml .= '<LIB_COURT><![CDATA['.$this->cleanCut($product->getName(),70,'').']]></LIB_COURT>';
					$_xml .= '<TYPE_ART>'.$type.'</TYPE_ART>';
				$_xml .= '</PRODUIT>
';
				if($number){
					foreach($colis as $coli){
						$_xml .= '<PRODUIT>';
							$_xml .= '<CODE_ART>'.$product->entity_id.'</CODE_ART>';
                            $_xml .= '<EAN>'.$product->getCodeBarre().'</EAN>';
							$_xml .= '<REFERENCE_ART>'.$this->cleanCut($coli,18,'').'</REFERENCE_ART>';
							$_xml .= '<LIB_COURT><![CDATA['.$this->cleanCut($product->getName(),70,'').'-'.$i.']]></LIB_COURT>';
							$_xml .= '<TYPE_ART>PRO</TYPE_ART>';
						$_xml .= '</PRODUIT>
';
						$i++;
					}
				}
			}

			
			$nom_fichier = 'etat-des-stocks_'.Mage::getModel('core/date')->date('Ymd-His').'_'.Mage::getModel('core/date')->date('Ymd').'.XML';
			$chemin_fichier = Mage::getBaseDir('base').'/uploads/export/etat-des-stocks/';
			unlink($chemin_fichier.$nom_fichier);
			$fichier = fopen($chemin_fichier.$nom_fichier,'x+');
			fwrite($fichier, $_xml);
			fclose($fichier);

			$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);

		} catch (Exception $e) {
			Mage::printException($e);
		}
	}

	public function kitsExport() {
		try {
			$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

			$_xml= '<?xml version="1.0"?>
';
			foreach($collection as $product){
				$colis = explode("\n",rtrim($product->shipping_sku));
				if(count($colis) > 1){
					foreach($colis as $coli){
						$_xml .= '<LOT>';
							$_xml .= '<REFERENCE_LOT>'.trim($product->getSku()).'</REFERENCE_LOT>';
							$_xml .= '<REFERENCE_COMPOSANT>'.trim($coli).'</REFERENCE_COMPOSANT>';
							$_xml .= '<QTE>1</QTE>';
						$_xml .= '</LOT>
';
					}
				}
			}

			$nom_fichier = 'liste-des-lots_'.Mage::getModel('core/date')->date('Ymd-His').'_'.Mage::getModel('core/date')->date('Ymd').'.xml';
			$chemin_fichier = Mage::getBaseDir('base').'/uploads/export/liste-des-lots/';
			unlink($chemin_fichier.$nom_fichier);
			$fichier = fopen($chemin_fichier.$nom_fichier,'x+');
			fwrite($fichier, $_xml);
			fclose($fichier);

			$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);

		} catch (Exception $e) {
			Mage::printException($e);
		}
	}

	public function entetesCommandesExport()
	{
		try {
			$chemin_fichier = Mage::getBaseDir('base').'/uploads/export/lcmd/';
			$db_read        = Mage::getSingleton('core/resource')->getConnection('core_read');;

            $collection = Mage::getModel("sales/order_invoice")->getCollection();
			$collection->addAttributeToSelect('*');
			/*
			$collection->addAttributeToFilter('created_at', array(
				'from' => date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time() - 3600 * 24)),
				'to'   => date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time())),
			));
			*/
			$collection->getSelect()->join(
				array('invoice_status_table' => SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TABLE_NAME),
				sprintf(
					'main_table.entity_id = invoice_status_table.invoice_id AND invoice_status_table.invoice_status LIKE %s',
					$db_read->quote(SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TO_BE_EXPORTED)
				),
				array('invoice_status_table.invoice_status')
			);

			$_xml= '<?xml version="1.0"?>
';

			$tab = array();
			foreach($collection as $invoice){
				$order 				= $invoice->getOrder();
				$shipping_address 	= $order->getShippingAddress();
				$billing_address 	= $order->getBillingAddress();

				$invoiceData 			= $invoice->getData();
				$orderData 				= $order->getData();
				$payment 				= $order->getPayment()->getMethodInstance();
				$_history 				= $order->getAllStatusHistory();
				if(!$shipping_address)
					$shipping_address=$billing_address;

				$shipping_addressData 	= $shipping_address->getData();
				$billing_addressData 	= $billing_address->getData();

				$shipping_addressArray = str_split($shipping_addressData['street'].' '.$shipping_addressData['etage'].' '.$shipping_addressData['batiment'], 38);
				$billing_addressArray = str_split($billing_addressData['street'].' '.$billing_addressData['etage'].' '.$billing_addressData['batiment'], 38);

				$countryShipping = Mage::getModel('directory/country')->load($shipping_addressData['country_id'])->getName();
				$countryBilling = Mage::getModel('directory/country')->load($billing_addressData['country_id'])->getName();

				$codeCarrier='';

				if(substr($orderData['shipping_method'],16,9) == "COLISSIMO" || $orderData['shipping_method']=="adminshipping_adminshipping"){
					$codeCarrier = "COLISSIMO";
				}
				elseif(substr($orderData['shipping_method'],16,10) == "MESSAGERIE" || $orderData['shipping_method']=="adminshipping2_adminshipping2"){
					$codeCarrier = "MESSAGERIE";
				}
				elseif(substr($orderData['shipping_method'],16,3) == "IMX"){
					$codeCarrier = "IMX";
				}
				elseif(substr($orderData['shipping_method'],16,3) == "B2C"){
					$codeCarrier = "B2C";
				}
                elseif(substr($orderData['shipping_method'],0,12) == "pointsrelais"){
                    $codeCarrier = "MONDIAL_RELAY";
                }
				else
					$codeCarrier=$orderData['shipping_method'];

				if($orderData['customer_group_id'] == 3)$btob = 'BtoB';
				else $btob = 'BtoC';

                //if($orderData['entity_id']==3753)continue;
			        //print_r(substr($orderData["shipping_method"],0,12));die;

				$_xml .= '<COMMANDE>';
					$_xml .= '<NUM_CMDE>'.$orderData['increment_id'].'</NUM_CMDE>';
					$_xml .= '<NAT_CMDE></NAT_CMDE>';
					$_xml .= '<TYPE_CMDE>'.$btob.'</TYPE_CMDE>';
					$_xml .= '<CODE_CLIENT>'.$orderData['customer_id'].'</CODE_CLIENT>';
					$_xml .= '<NUM_FACTURE_BL>'.$invoiceData['increment_id'].'</NUM_FACTURE_BL>';
					$_xml .= '<DATE_EDITION>'.Mage::getModel('core/date')->date('Ymd',$invoiceData['updated_at']).'</DATE_EDITION>';
					$_xml .= '<NUMIC></NUMIC>';
					$_xml .= '<SOCIETE_FAC><![CDATA['.$this->cleanCut($billing_addressData['company'],50,'').']]></SOCIETE_FAC>';
					$_xml .= '<CIVILITE_FAC>'.$this->cleanCut($billing_addressData['genre'],20,'').'</CIVILITE_FAC>';
					$_xml .= '<NOM_CLIENT_FAC><![CDATA['.$this->cleanCut($billing_addressData['lastname'],50,'').']]></NOM_CLIENT_FAC>';
					$_xml .= '<PRENOM_CLIENT_FAC><![CDATA['.$this->cleanCut($billing_addressData['firstname'],40,'').']]></PRENOM_CLIENT_FAC>';
					$_xml .= '<ADR1_FAC><![CDATA['.$this->cleanCut($this->cleanAdresse($billing_addressArray[0]),38,'').']]></ADR1_FAC>';
					$_xml .= '<ADR2_FAC><![CDATA['.$this->cleanCut($this->cleanAdresse($billing_addressArray[1]),38,'').']]></ADR2_FAC>';
					$_xml .= '<ADR3_FAC><![CDATA['.$this->cleanCut($this->cleanAdresse($billing_addressArray[2]),38,'').']]></ADR3_FAC>';
					$_xml .= '<ADR4_FAC><![CDATA['.$this->cleanCut($this->cleanAdresse($billing_addressArray[3]),38,'').']]></ADR4_FAC>';
					$_xml .= '<CP_FAC>'.$this->cleanCut($billing_addressData['postcode'],10,'').'</CP_FAC>';
					$_xml .= '<VILLE_FAC>'.$this->cleanCut($billing_addressData['city'],38,'').'</VILLE_FAC>';
					$_xml .= '<ETAT_FAC>'.$this->cleanCut($billing_addressData['region'],38,'').'</ETAT_FAC>';
					$_xml .= '<PAYS_FAC>'.$this->cleanCut($countryBilling,38,'').'</PAYS_FAC>';
					$_xml .= '<CODE_ISO_FAC>'.$this->cleanCut($billing_addressData['country_id'],2,'').'</CODE_ISO_FAC>';
					$_xml .= '<SOCIETE_LIV>'.$this->cleanCut($shipping_addressData['company'],38,'').'</SOCIETE_LIV>';
					$_xml .= '<CIVILITE_LIV>'.$this->cleanCut($shipping_addressData['genre'],20,'').'</CIVILITE_LIV>';
					$_xml .= '<NOM_CLIENT_LIV>'.$this->cleanCut($shipping_addressData['lastname'],50,'').'</NOM_CLIENT_LIV>';
					$_xml .= '<PRENOM_CLIENT_LIV>'.$this->cleanCut($shipping_addressData['firstname'],40,'').'</PRENOM_CLIENT_LIV>';
					$_xml .= '<ADR1_LIV><![CDATA['.$this->cleanCut($this->cleanAdresse($shipping_addressArray[0]),38,'').']]></ADR1_LIV>';
					$_xml .= '<ADR2_LIV><![CDATA['.$this->cleanCut($this->cleanAdresse($shipping_addressArray[1]),38,'').']]></ADR2_LIV>';
					$_xml .= '<ADR3_LIV><![CDATA['.$this->cleanCut($this->cleanAdresse($shipping_addressArray[2]),38,'').']]></ADR3_LIV>';
					$_xml .= '<ADR4_LIV><![CDATA['.$this->cleanCut($this->cleanAdresse($shipping_addressArray[3]),38,'').']]></ADR4_LIV>';
					$_xml .= '<CP_LIV>'.$this->cleanCut($shipping_addressData['postcode'],10,'').'</CP_LIV>';
					$_xml .= '<VILLE_LIV>'.$this->cleanCut($shipping_addressData['city'],38,'').'</VILLE_LIV>';
					$_xml .= '<ETAT_LIV>'.$this->cleanCut($shipping_addressData['region'],38,'').'</ETAT_LIV>';
					$_xml .= '<PAYS_LIV>'.$this->cleanCut($countryShipping,38,'').'</PAYS_LIV>';
					$_xml .= '<CODE_ISO_LIV>'.$this->cleanCut($shipping_addressData['country_id'],2,'').'</CODE_ISO_LIV>';
					$_xml .= '<TELEPHONE_LIV>'.$this->cleanCut($shipping_addressData['telephone'],20,'').'</TELEPHONE_LIV>';
					$_xml .= '<EMAIL_LIV>'.$this->cleanCut($shipping_addressData['email'],50,'').'</EMAIL_LIV>';
					$_xml .= '<TYPE_ENVOI></TYPE_ENVOI>'; 
					$_xml .= '<CODE_TRANSPORTEUR_DEDIE>'.$codeCarrier.'</CODE_TRANSPORTEUR_DEDIE>';
					$_xml .= '<TAUX_TVA_1></TAUX_TVA_1>';
					$_xml .= '<BASE_TVA_1></BASE_TVA_1>';
					$_xml .= '<MONTANT_TVA_1>'.$invoiceData['base_tax_amount'].'</MONTANT_TVA_1>';
					$_xml .= '<TAUX_TVA_2></TAUX_TVA_2>';
					$_xml .= '<BASE_TVA_2></BASE_TVA_2>';
					$_xml .= '<MONTANT_TVA_2></MONTANT_TVA_2>';
					$_xml .= '<TAUX_TVA_3></TAUX_TVA_3>';
					$_xml .= '<BASE_TVA_3></BASE_TVA_3>';
					$_xml .= '<MONTANT_TVA_3></MONTANT_TVA_3>';
					$_xml .= '<MONTANT_ESCOMPTE></MONTANT_ESCOMPTE>';
					$_xml .= '<MONTANT_ACOMPTE></MONTANT_ACOMPTE>';
					$_xml .= '<MONTANT_FRAIS_ENVOI_HT>'.$invoiceData['shipping_hidden_tax_amount'].'</MONTANT_FRAIS_ENVOI_HT>';
					$_xml .= '<MONTANT_FRAIS_ENVOI_TTC>'.$invoiceData['base_shipping_incl_tax'].'</MONTANT_FRAIS_ENVOI_TTC>';
					$_xml .= '<MONTANT_TOTAL_HT>'.$invoiceData['subtotal'].'</MONTANT_TOTAL_HT>';
					$_xml .= '<MONTANT_TOTAL_TTC>'.$invoice->getGrandTotal().'</MONTANT_TOTAL_TTC>';
					$_xml .= '<DATE_ECHEANCE></DATE_ECHEANCE>';
					$_xml .= '<MODE_PAIEMENT>'.$this->cleanCut($payment->getTitle(),30,'').'</MODE_PAIEMENT>';
					$_xml .= '<DATE_PAIEMENT></DATE_PAIEMENT>';
					$_xml .= '<MONTANT_PAIEMENT></MONTANT_PAIEMENT>';
					$_xml .= '<RESTANT_DU></RESTANT_DU>';
					$_xml .= '<DEVISE>'.$invoiceData['base_currency_code'].'</DEVISE>';
					$_xml .= '<TYPE_FACTURE>Facture</TYPE_FACTURE>';
					$_xml .= '<COMMENTAIRE_1_COMMANDE><![CDATA['.$this->cleanCut($_history[0],50,'').']]></COMMENTAIRE_1_COMMANDE>';
					$_xml .= '<COMMENTAIRE_2_COMMANDE><![CDATA['.$this->cleanCut($_history[1],50,'').']]></COMMENTAIRE_2_COMMANDE>';
					$_xml .= '<COMMENTAIRE_3_COMMANDE><![CDATA['.$this->cleanCut($_history[2],50,'').']]></COMMENTAIRE_3_COMMANDE>';
                    if($codeCarrier == "MONDIAL_RELAY"){
                        $_xml .= '<CODE_PAYS_POINT>'.$this->cleanCut($billing_addressData['country_id'],2,'').'</CODE_PAYS_POINT>';
                        $_xml .= '<NUM_POINT>'.substr($orderData["shipping_method"],-5,5).'</NUM_POINT>';
                    }
				$_xml .= '</COMMANDE>';


				$pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
				$nom_fichier_pdf = 'Facture_'.$invoiceData['increment_id'].'.pdf';
				unlink($chemin_fichier.$nom_fichier_pdf);
				$fichier_pdf = fopen($chemin_fichier.$nom_fichier_pdf,'x+');
				fwrite($fichier_pdf, $pdf->render());
				rewind($fichier_pdf);
				fclose($fichier_pdf);
				$tab[] = $nom_fichier_pdf;

				$invoice->updateExportStatus('header', 1);
				if ($invoice->getExportStatus('detail')) {
					$invoice->updateCurrentStatus(SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_EXPORTED);
				}
			}

			foreach($tab as $nom_fichier){
				$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);
			}

			$nom_fichier = 'lcmd-'.Mage::getModel('core/date')->date('Ymd-His').'_'.Mage::getModel('core/date')->date('Ymd').'.xml';
			unlink($chemin_fichier.$nom_fichier);
			$fichier = fopen($chemin_fichier.$nom_fichier,'x+');
			fwrite($fichier, $_xml);
			rewind($fichier);
			fclose($fichier);

			$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);
		} catch (Exception $e) {
			Mage::printException($e);
		}
	}

	public function detailsCommandesExport()
	{
		try {
			$db_read = Mage::getSingleton('core/resource')->getConnection('core_read');;

			$collection = Mage::getModel("sales/order_invoice")->getCollection();
			$collection->addAttributeToSelect('*');
			/*
			$collection->addAttributeToFilter('created_at', array(
				'from' => date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time() - 3600 * 24)),
				'to'   => date('Y-m-d H:i:s', Mage::getModel('core/date')->timestamp(time())),
			));
			*/
			$collection->getSelect()->join(
				array('invoice_status_table' => SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TABLE_NAME),
				sprintf(
					'main_table.entity_id = invoice_status_table.invoice_id AND invoice_status_table.invoice_status LIKE %s',
					$db_read->quote(SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TO_BE_EXPORTED)
				),
				array('invoice_status_table.invoice_status')
			);
			
//             $collection->getSelect()->join(
//                 array('invoice_status_table' => SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_TABLE_NAME),
//                 'main_table.entity_id = invoice_status_table.invoice_id AND main_table.increment_id=100005569',
//                 array('invoice_status_table.invoice_status')
//             );

			$_xml= '<?xml version="1.0"?>
';

			foreach($collection as $invoice){
				$items 				= $invoice->getAllItems();
				$invoiceData 		= $invoice->getData();

				foreach ($invoice->getItemsCollection()->addAttributeToSort('sku', 'desc') as $item){
					$itemData = $item->getData();

					$product=Mage::getModel("catalog/product")->load($itemData["product_id"]);
					if($product && $product->getData("type_id")=="bundle")continue;

					$tauxTva = number_format(((($itemData['base_price_incl_tax'] / $itemData['price']) * 100) - 100),2,'.','');
					if($tauxTva < 0 || $tauxTva > 100) $tauxTva =0;

					$tauxRemise = number_format((100 / ($itemData['price'] / $itemData['discount_amount'])),2,'.','');
					if($tauxRemise < 0 || $tauxRemise > 100) $tauxRemise =0;

					$_xml .= '<COMMANDEDETAILS>';
						$_xml .= '<NUM_FACTURE_BL>'.$invoiceData['increment_id'].'</NUM_FACTURE_BL>';
						$_xml .= '<CODE_ART>'.$itemData['product_id'].'</CODE_ART>';
						$_xml .= '<REFERENCE_ART>'.trim($itemData['sku']).'</REFERENCE_ART>';
						$_xml .= '<LIBELLE_ART><![CDATA['.$this->cleanCut($itemData['name'],150,'').']]></LIBELLE_ART>';
						$_xml .= '<QTE>'.number_format($itemData['qty'],0,'.','').'</QTE>';
						$_xml .= '<OBLIGATOIRE>O</OBLIGATOIRE>';
						$_xml .= '<PRIX_UNITAIRE_HT>'.$itemData['price'].'</PRIX_UNITAIRE_HT>';
						$_xml .= '<TAUX_TVA>'.$tauxTva.'</TAUX_TVA>';
						$_xml .= '<REMISE>'.$itemData['discount_amount'].'</REMISE>';
						$_xml .= '<TAUX_REMISE>'.$tauxRemise.'</TAUX_REMISE>';
						$_xml .= '<MONTANT_TOTAL_LIGNE_HT>'.($itemData['price'] * $itemData['qty']).'</MONTANT_TOTAL_LIGNE_HT>';
					$_xml .= '</COMMANDEDETAILS>
';
				}

				$invoice->updateExportStatus('detail', 1);
				if ($invoice->getExportStatus('header')) {
					$invoice->updateCurrentStatus(SDW_Sales_Model_Order_Invoice::INVOICE_STATUS_EXPORTED);
				}
			}
			

			$nom_fichier = 'lart-'.Mage::getModel('core/date')->date('Ymd-His').'_'.Mage::getModel('core/date')->date('Ymd').'.xml';
			$chemin_fichier = Mage::getBaseDir('base').'/uploads/export/lart/';
			unlink($chemin_fichier.$nom_fichier);
			$fichier = fopen($chemin_fichier.$nom_fichier,'x+');
			fwrite($fichier, $_xml);
			fclose($fichier);

			$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);
		} catch (Exception $e) {
			Mage::printException($e);
		}
	}

	public function commandesFournisseursExport() {
		try {
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');

			$providerOrder = $read->fetchAll("SELECT `mgt_sdw_provider_order`.* , `mgt_sdw_provider`.sociale, `mgt_sdw_provider`.delai FROM `mgt_sdw_provider_order`
													LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
													WHERE `mgt_sdw_provider_order`.status = 'Livré' AND `mgt_sdw_provider_order`.ads=0 ");
			$_xml= '<?xml version="1.0"?>
';
			foreach($providerOrder as $provider){
				$create = strtotime($provider['order_create']);
				$livraison = $create + ($provider['delai'] * 7 * 24 * 60 * 60);

				$product = Mage::getModel('catalog/product')->load($provider['product_id']);

				$_xml .= '<COMMANDE>';
					$_xml .= '<NUM_BL>'.$provider['provider_order_id'].'</NUM_BL>';
					$_xml .= '<DATE_PREVUE>'.Mage::getModel('core/date')->date('Ymd' , $livraison).'</DATE_PREVUE>';
					$_xml .= '<LIBELLE_FOURN><![CDATA['.$this->cleanCut($provider['sociale'],30,'').']]></LIBELLE_FOURN>';
					$_xml .= '<CODE_ART>'.$this->cleanCut($product->getSku(),18,'').'</CODE_ART>';
					$_xml .= '<CODE_ART_FOURN>'.$product->ref_fournisseur.'</CODE_ART_FOURN>';
					$_xml .= '<QTE_ATTENDUE>'.number_format($provider['quantity'],0,'.','').'</QTE_ATTENDUE>';
				$_xml .= '</COMMANDE>
';
				$write->query("UPDATE `mgt_sdw_provider_order` SET ads=1 WHERE id_provider_order=".$provider['id_provider_order']);
			}

			$nom_fichier = 'cfou-'.Mage::getModel('core/date')->date('Ymd-His').'_'.Mage::getModel('core/date')->date('Ymd').'.XML';
			$chemin_fichier = Mage::getBaseDir('base').'/uploads/export/cfou/';
			unlink($chemin_fichier.$nom_fichier);
			$fichier = fopen($chemin_fichier.$nom_fichier,'x+');
			fwrite($fichier, $_xml);
			fclose($fichier);

			$this->uploadFtp($chemin_fichier.$nom_fichier, $nom_fichier);

		} catch (Exception $e) {
			Mage::printException($e);
		}
	}
}
