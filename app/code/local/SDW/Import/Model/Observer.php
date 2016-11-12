<?php

set_time_limit(0);

class SDW_Import_Model_Observer
{
	private $IMPORT_HOST;
	private $IMPORT_LOGIN;
	private $IMPORT_PASS;
	private $IMPORT_DIR;

	/*
	private $IMPORT_HOST 	= "ftp.alpha-d-s.com";
	private $IMPORT_LOGIN 	= "powell";
	private $IMPORT_PASS	= "POW591adsn";
	private $IMPORT_DIR 	= "/POWELL/ADSN_Vers_POWELL";
	*/
	/*
	private $IMPORT_HOST 	= "46.18.192.100";
	private $IMPORT_LOGIN 	= "1664_depottest";
	private $IMPORT_PASS	= "depottest";
	private $IMPORT_DIR 	= "/colony/";
	*/

	private $_logs = array();
	private $history;

	public function __construct()
	{
		if ($_SERVER['HTTP_HOST'] === 'colony.sentinellesduweb.net') {
			$this->IMPORT_HOST  = "46.18.192.100";
			$this->IMPORT_LOGIN = "1664_depottest";
			$this->IMPORT_PASS  = "depottest";
			$this->IMPORT_DIR   = "/colony/";
		} else {
			$this->IMPORT_HOST  = "ftp.alpha-d-s.com";
			$this->IMPORT_LOGIN = "powell";
			$this->IMPORT_PASS  = "POW591adsn";
			$this->IMPORT_DIR   = "/POWELL/ADSN_Vers_POWELL";
		}
	}

	/**Gestion Lecture FTP**/
	private function downloadFtp($fichier_local, $type)
	{
		$conn_id = ftp_connect($this->IMPORT_HOST);
		$login_result = ftp_login($conn_id, $this->IMPORT_LOGIN, $this->IMPORT_PASS);

		if(!$login_result){
			mail("archives@sdw.bz","Debug colony-perroquet.fr Connexion FTP impossible","<pre>".print_r($this->logs,true)."</pre>", "From: support@sentinellesduweb.com\r\nReply-To: support@sentinellesduweb.com\r\nX-Mailer: PHP/".phpversion());
			if(!empty($this->history)){
				$this->history->setStock('Connexion FTP Impossible');
				$this->history->save();
			}
		}
		else {
			ftp_pasv($conn_id, false);

			//recuperation des fichiers
			$ftp_nlist = ftp_nlist($conn_id, $this->IMPORT_DIR);

			$ftp_nlist2 = array();
			$datetest = sprintf("%02d",Mage::getModel('core/date')->date('d'));

            $datetestStart = Mage::getModel('core/date')->date('Ym').$datetest."_".sprintf("%02d",Mage::getModel('core/date')->date('H') - 10);
			$datetestEnd = Mage::getModel('core/date')->date('Ym').$datetest."_".sprintf("%02d",Mage::getModel('core/date')->date('H'));

			foreach($ftp_nlist as $nom_fichier){
				if (strpos($nom_fichier,Mage::getModel('core/date')->date('Ym').$datetest)!==false && strpos($nom_fichier,$type)!==false){
					$position = strpos($nom_fichier,Mage::getModel('core/date')->date('Ym').$datetest);
                    $verif = substr($nom_fichier,$position, 11);    
               
                    if(strcmp($verif, $datetestStart) > 0 && //retourne > 0 si la date du fichier est supérieur à la date la plus ancienne autorisé
                        strcmp($datetestEnd, $verif) >= 0)//retourne <= 0 si la date du fichier est inférieur ou égale à l'heure actuel
                        $ftp_nlist2[] = $nom_fichier;
                }
			}
             
            rsort($ftp_nlist2); // pour n'importer que le dernier fichier créé

			if(!empty($ftp_nlist2)){
				//on parse le rep
				foreach($ftp_nlist2 as $nom_fichier_distant){
					//si le fichier commence par
					if (strpos($nom_fichier_distant,$type)!==false){
						// Tentative de téléchargement du fichier $nom_fichier_distant et sauvegarde dans le fichier $fichier_local
						if (ftp_get($conn_id, $fichier_local, $nom_fichier_distant, FTP_BINARY)) {
							//récupération du fichier a parser

							switch($type){
								case 'Infos_articles'	:$this->parseStocksImport($fichier_local);break;
								case 'Infos_mvts_stock'	:
														$this->history->setFileAds($nom_fichier_distant);
														$this->history->save();
														$this->parseMouvementsStockImport($fichier_local);
														break;
								case 'Infos_colis'		:$this->parseTrackingImport($fichier_local);break;
							}
						}
						else {
							mail("archives@sdw.bz","Debug colony-perroquet.fr téléchargement impossible : ".$nom_fichier_distant,"<pre>".print_r($this->logs,true)."</pre>", "From: support@sentinellesduweb.com\r\nReply-To: support@sentinellesduweb.com\r\nX-Mailer: PHP/".phpversion());
							if(!empty($this->history)){
								$this->history->setStock('Téléchargement impossible pour le fichier '.$nom_fichier_distant);
								$this->history->save();
							}
						}
                        break;// pour n'importer que le dernier fichier créé
					}
				}
			}
			else{
				if(!empty($this->history)){
					$this->history->setStock('Aucun fichier à importer');
					$this->history->save();
				}
			}
		}

		ftp_close($conn_id);
	}


	public function stocksImport() {

		//Correspond à Infos_articles
		$nom_fichier = 'Infos_articles_'.Mage::getModel('core/date')->date('Ymd-His').'.xml';
		$chemin_fichier = Mage::getBaseDir('base').'/uploads/import/Infos_articles/';
		@unlink($chemin_fichier.$nom_fichier); 

		$this->downloadFtp($chemin_fichier.$nom_fichier,'Infos_articles'); 

		mail("archives@sdw.bz","Debug Import colony-perroquet.fr","<pre>".print_r($this->logs,true)."</pre>", "From: support@sentinellesduweb.com\r\nReply-To: support@sentinellesduweb.com\r\nX-Mailer: PHP/".phpversion());
	}

	public function parseStocksImport($fichier_local) {
		$this->logs[]="Traitement de $fichier_local";

		libxml_use_internal_errors(true);
		$xml = simplexml_load_file($fichier_local);
		if ($xml === false) {
			$this->logs[]= "Failed loading XML";
			foreach(libxml_get_errors() as $error) {
				$this->logs[]= $error->message;
			}
		}
		else{
/*
			$pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
			foreach ($pCollection as $process)
				$process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();
*/
			foreach($xml->ARTICLE as $child){
				$sku	= (string)$child->CODE_ART;
				$qty 	= (int)$child->QTE_DISPO;
				$weight	= (float)$child->POIDS;
				$this->logs[]="&nbsp;&nbsp;$sku = $qty";

				$tabSku = explode('-',$sku);
				$nbTabSku =  count($tabSku);

				if(substr($sku,-2,1) == '-')
				{
					$sku = explode('-',$sku,-1);
					$sku=$sku[0];
				}

				if(!isset($totalsweight[$sku]))$totalsweight[$sku]=0;
				$totalsweight[$sku]+=($weight/1000);

				$Item = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);

				if ($Item) {
					$this->logs[]= "Chargement ok de $sku<br />\n";
					try {
// 						$Item = Mage::getModel('catalog/product')->load($Item->entity_id);
// 						$Item->setWeight($totalsweight[$sku]);
// 						$Item->save();

						$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($Item->entity_id);
						if($stockItem->getId()){
							//$stockItem->getData('manage_stock') cette ancien appel ne prend pas en compte toutes les vérifications.
							if($stockItem->getManageStock() == 1){
								if($qty > $stockItem->getData('min_qty'))$stockItem->setData('is_in_stock', 1);
								else $stockItem->setData('is_in_stock', 0);
							}
							$stockItem->setData('qty', $qty);
							$stockItem->save();
						}
					}
					catch (Exception $e) {
						Mage::printException($e);
					}
				}
			}
/*

			$pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
			foreach ($pCollection as $process){
				$process->reindexEverything();
				$process->setMode(Mage_Index_Model_Process::MODE_REAL_TIME)->save();
			}*/
		}
	}

	public function mouvementsStockImport()
	{
		//Correspond à Infos_mvts_stock
        $chemin_fichier = Mage::getBaseDir('base').'/uploads/import/Infos_mvts_stock/';
		$nom_fichier = 'Infos_mvts_stock_'.Mage::getModel('core/date')->date('Ymd-His').'.xml';

		
        
//         foreach(scandir($chemin_fichier."manual") as $file)
//         {
//             if(is_dir($file))continue;
//             
//             $this->history = Mage::getModel('stockhistory/stockhistory');
//             $this->history->setDate(time());
//             $this->history->save();
//             $this->parseMouvementsStockImport($chemin_fichier."manual/".$file);
//             die;
//         }
		
		
		$this->history = Mage::getModel('stockhistory/stockhistory');
		$this->history->setDate(time());
		$this->history->save();
		
		if(!file_exists($chemin_fichier.$nom_fichier)){
			$this->downloadFtp($chemin_fichier.$nom_fichier, 'Infos_mvts_stock');
		}
		else {
			$this->history->setStock('Fichier déjà importé');
			$this->history->save();
		}
	}

	public function parseMouvementsStockImport($fichier_local) {
		$this->history->setFileLocal($fichier_local);
		$this->history->save();

		$this->logs[]="Traitement de $fichier_local";
		libxml_use_internal_errors(true);
		$xml = simplexml_load_file($fichier_local);
		if ($xml === false) {
			$stockHtml = "Failed loading XML<br/>";
			foreach(libxml_get_errors() as $error) {
				$stockHtml .= $error->message;
			}
			$this->history->setStock($stockHtml);
		}
		else {
			$stockHtml = '<table>';
			foreach($xml->MVT_STOCK as $child){
				$sku 			= (string)$child->CODE_ART;
				$qtyUpdate 		= (string)$child->QTE;
				$type 			= (string)$child->TYPEMVT;

				$product['CODEMVT']		= (string)$child->CODEMVT;
				$product['CODE_ART'] 	= (string)$child->CODE_ART;
				$product['DATEMVT'] 	= (string)$child->DATEMVT;
				$product['NUMBL'] 		= (string)$child->NUMBL;
				$product['COMMENTAIRE'] = (string)$child->COMMENTAIRE;

				/*
				if(substr($sku,-2,1) == '-')
				{
					$sku = explode('-',$sku,-1);
					$sku=$sku[0];
				}
				*/

				// Demande de Maxime du 27/06/2014: on ne prend en compte que les SKU (codes colis) qui finissent par "-1"
				// (ainsi que les SKU sans suffixe relatif au code colis)
				if (!preg_match('#^(\w+)(?:\-1)?$#', $sku, $actual_sku_match)) {
					continue;
				}
				$sku = $actual_sku_match[1];

				$Item = Mage::getModel('catalog/product')->loadByAttribute('sku',$sku);
				if($type == 'E' || ($type=='S' && $product['CODEMVT']!="CMD")){
					if ($Item) {
						$this->logs[]= "Chargement ok de $sku<br />\n";
						try {
							$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($Item->entity_id);
							if($stockItem->getId()){
								//$stockItem->getData('manage_stock') cette ancien appel ne prend pas en compte toutes les vérifications.
								if($stockItem->getManageStock() == 1){
									$qtyOld = number_format($stockItem->getData('qty'),0);

									$qty = $qtyOld + $qtyUpdate ;
									if($qty > $stockItem->getData('min_qty'))$stockItem->setData('is_in_stock', 1);
									else $stockItem->setData('is_in_stock', 0);

									$stockItem->setData('qty', $qty);
									$stockItem->save();

									$stockHtml .= '<tr><td>Ref : '.$sku.'</td><td>Ancien stock : '.$qtyOld .'<td>Nouveau stock : '.$qty.'</td></tr>';
								}
								else $stockHtml .= '<tr><td>Ref : '.$sku.'</td><td colspan="2">Pas de gestion de stock</td></tr>';
							}
						}
						catch (Exception $e) {
							Mage::printException($e);
						}
					}
					else $stockHtml .= '<tr><td>Ref : '.$sku.'</td><td colspan="2">Pas de produit correspondant</td></tr>';
				}
			}
			$stockHtml .= '</table>';
			$this->history->setStock($stockHtml);
			$this->history->setStatus(1);
		}

		$this->history->save();
	}

	public function trackingImport() {
		//Correspond à Infos_colis
		$nom_fichier = 'Infos_colis_'.Mage::getModel('core/date')->date('Ymd-His').'.xml';
		$chemin_fichier = Mage::getBaseDir('base').'/uploads/import/Infos_colis/';
		unlink($chemin_fichier.$nom_fichier);

		$this->downloadFtp($chemin_fichier.$nom_fichier, 'Infos_colis');
		//$fichier_local = 'Infos_colis_20131211-233001.xml';
		//$this->parseTrackingImport($chemin_fichier.$fichier_local);

		mail("archives@sdw.bz","Debug Import tracking colony-perroquet.fr","<pre>".print_r($this->logs,true)."</pre>", "From: support@sentinellesduweb.com\r\nReply-To: support@sentinellesduweb.com\r\nX-Mailer: PHP/".phpversion());
	}

	public function parseTrackingImport($fichier_local) {
		$this->logs[]="Traitement de $fichier_local";
		libxml_use_internal_errors(true);
		$xml = simplexml_load_file($fichier_local);
		if ($xml === false) {
			$this->logs[]= "Failed loading XML\n";
			foreach(libxml_get_errors() as $error) {
				$this->logs[]= $error->message;
			}
		}
		else{
			foreach($xml->COLIS as $child){
				$params['NUM_CMDE'] 		= (string)$child->NUM_CMDE;
				$params['NUM_FACTURE_BL']	= (int)$child->NUM_FACTURE_BL;
				$params['STATUT'] 			= (string)$child->STATUT;// B=bloqué, 0=intégré, P=En préparation, T=Préparation terminée, E=Expédié (statut final), S=Annulé (statut final)
				$params['TYPE_ENVOI'] 		= (string)$child->TYPE_ENVOI;
				$params['NUM_TRACKING'] 	= (string)$child->NUM_TRACKING;
				$params['DATE_EXPED'] 		= (string)$child->DATE_EXPED;

				$order  = Mage::getModel("sales/order")->loadByIncrementId($params['NUM_CMDE']);
				if($order->getId()){
					$this->logs[]="&nbsp;&nbsp;".$order->getId()." = ".$params['STATUT']." = ".$params['NUM_TRACKING'];
					switch($params['STATUT']){
						case 'E':
								$codeCarrier='';
								if(substr($params['TYPE_ENVOI'],0,9) == "COLISSIMO"){
									$codeCarrier = "COLISSIMO";
								}
								elseif(substr($params['TYPE_ENVOI'],0,10) == "MESSAGERIE"){
									$codeCarrier = "MESSAGERIE";
								}
								elseif(substr($params['TYPE_ENVOI'],0,3) == "IMX"){
									$codeCarrier = "IMX";
								}

								/***Création Facture****/
								try
								{
									if($order->canInvoice()){
										$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

										if (!$invoice->getTotalQty()) {
											Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
										}

										$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
										$invoice->register();
										$transactionSave = Mage::getModel('core/resource_transaction')
											->addObject($invoice)
											->addObject($invoice->getOrder())
											->save();
									}

									/***Création expédition***/
									$convertOrder = new Mage_Sales_Model_Convert_Order();
									$shipment = $convertOrder->toShipment($order);

									$items = $order->getAllItems();
									$qty = 0;
									if (count($items))
									{
										foreach ($items as $item) {
											$shipped_item = $convertOrder->itemToShipmentItem($item);

// 											$qty_liv = $item->getQtyOrdered() - $item->getQtyRefunded();
                                            $qty_liv = $item->getQtyToShip();

											$shipped_item->setQty($qty_liv);
											$shipment->addItem($shipped_item);
											$qty += $qty_liv;
										}
										$shipment->setTotalQty($qty);
									}
									$tracking = array(
										'carrier_code' => $codeCarrier,
										'title' => $params['TYPE_ENVOI'],
										'number' => $params['NUM_TRACKING'],
									);

									$_track = Mage::getModel('sales/order_shipment_track')->addData($tracking);
									$shipment->addTrack($_track);
									$emailSentStatus = $shipment->getData('email_sent');
									$customerEmail = $order->getData('customer_email');
									if (!is_null($customerEmail) && !$emailSentStatus) {
										if(!empty($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'],array("31.39.24.91","80.13.123.44","88.160.94.181"))){
											mail("archives@sdw.bz","Debug ".$_SERVER['HTTP_HOST'],"<pre>".print_r(array("Envoi de l'email"),true)."</pre>", "From: support@sentinellesduweb.com\r\nReply-To: support@sentinellesduweb.com\r\nX-Mailer: PHP/".phpversion());
										}
										$shipment->sendEmail();
										$shipment->setEmailSent(true);
									}
									$save = Mage::getModel('core/resource_transaction')
											->addObject($shipment)
											->addObject($shipment->getOrder())
											->save();


									/****Modification statut commande***/
								//	$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
									$order->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
									$order->setStatus("complete");
									$order->save();
								}
								catch (Mage_Core_Exception $e) {
								}

								break;
						case '0':
						case 'T':
						case 'P':
								/**
								 * change order status to 'Processing'
								 */
								//$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
								$order->setData('state', Mage_Sales_Model_Order::STATE_PROCESSING);
								$order->setStatus("processing");
								$order->save();
								break;
						case 'B':
								/**
								 * change order status to 'Closed'
								 */
								//$order->setState(Mage_Sales_Model_Order::STATE_CLOSED, true);
								$order->setData('state', Mage_Sales_Model_Order::STATE_CLOSED);
								if($order->canHold())$order->hold()->save();
								$order->setStatus("closed");
								$order->save();
								break;
						case 'S':
								/**
								 * change order status to 'Canceled'
								 */
								//$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
								$order->setData('state', Mage_Sales_Model_Order::STATE_CANCELED);
								if($order->canCancel())$order->cancel()->save();
								$order->setStatus("canceled");
								$order->save();
								break;
					}
				}
			}
		}
	}
}
