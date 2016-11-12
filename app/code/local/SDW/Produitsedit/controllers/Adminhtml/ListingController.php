<?php
class SDW_Produitsedit_Adminhtml_ListingController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}

	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}

	public function saveAction()
    {
		/*******Gestion des sku produits********/
		$data = $this->getRequest()->getPost('sku');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $sku) {
				$Item = Mage::getModel('catalog/product')->load($productId);

				if ($Item) {
					if($Item->sku != $sku){
						$change_product = true;
						$Item->setSku($sku);
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de réf. ont été sauvegardée'));
	        }
        }

		/*******Gestion des ref_fournisseur produits********/
		$data = $this->getRequest()->getPost('ref_fournisseur');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $ref_fournisseur) {
				$Item = Mage::getModel('catalog/product')->load($productId);

				if ($Item) {
					if($Item->ref_fournisseur != $ref_fournisseur){
						$change_product = true;
						$Item->setRef_fournisseur($ref_fournisseur);
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de réf. Fournisseur ont été sauvegardée'));
	        }
        }

		/*******Gestion des prix d'achat produits********/
		$data_achat = $this->getRequest()->getPost('prix_achat');
        $errors = array();
        if (count($data_achat)>0) {
			$change_product = false;
	        foreach ($data_achat as $productId => $prix_achat) {
	            try {
	                $Item = Mage::getModel('catalog/product')->load($productId);
	                if ($Item) {
						$priceCourant = $Item->getData('prix_achat');
						if($prix_achat != number_format($priceCourant,2,',','') && $prix_achat!=0.00){
							$change_product = true;
							$Item->setPrix_achat($prix_achat);
							$Item->save();
						}
	                }
	            } catch (Mage_Core_Exception $e) {
	                Mage::logException($e);
	                $this->_getSession()->addException($e->getMessage());
	                $errors[] = $e->getMessage();
	            } catch (Exception $e) {
	                Mage::logException($e);
	                $this->_getSession()->addException($this->__('Le prix d\'achat pour le produit id: %d n\'a pu être sauvegardé', $productId));
	                $errors[] = $e->getMessage();
	            }
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de prix d\'achat ont été sauvegardée'));
	        }
        }

		/*******Gestion des prix d'achat produits********/
		$data_achat_dollar = $this->getRequest()->getPost('prix_achat_dollar');
		$rates = Mage::getModel('directory/currency')->getCurrencyRates('EUR', 'USD');
        $errors = array();
        if (count($data_achat_dollar)>0) {
			$change_product = false;
	        foreach ($data_achat_dollar as $productId => $prix_achat_dollar) {
	            try {
	                $Item = Mage::getModel('catalog/product')->load($productId);
	                if ($Item) {
						$priceCourant = $Item->getData('prix_achat_dollar');
						if($prix_achat_dollar != number_format($priceCourant,4,',','') && $prix_achat_dollar!=0.00){
							$change_product = true;
							$Item->setPrix_achat_dollar($prix_achat_dollar);

							$prix_achat_temp = number_format((str_replace(",",".",$prix_achat_dollar) * (1/$rates['USD'])),4,',','');
							$Item->setPrix_achat($prix_achat_temp);

							$Item->save();
						}
	                }
	            } catch (Mage_Core_Exception $e) {
	                Mage::logException($e);
	                $this->_getSession()->addException($e->getMessage());
	                $errors[] = $e->getMessage();
	            } catch (Exception $e) {
	                Mage::logException($e);
	                $this->_getSession()->addException($this->__('Le prix d\'achat en dollars pour le produit id: %d n\'a pu être sauvegardé', $productId));
	                $errors[] = $e->getMessage();
	            }
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de prix d\'achat en dollars ont été sauvegardée'));
	        }
        }

		/*******Gestion des prix spécial produits********/
		$data = $this->getRequest()->getPost('special_price');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $special_price) {
				$Item = Mage::getModel('catalog/product')->load($productId);
				if ($Item) {

					// Get the product's tax class' ID
					$taxClassId = $Item->getData("tax_class_id");
					// Get the tax rates of each tax class in an associative array
					$taxClasses = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass());
					// Extract the tax rate from the array
					$taxRate = $taxClasses["value_".$taxClassId];

					$special_price_ht = (float)str_replace(',','.',$special_price) / (($taxRate/100) +1);

					if(number_format($Item->special_price,4,',','') != number_format($special_price_ht,4,',','') && number_format($special_price_ht,4,',','') != number_format($Item->getFinalPrice(),4,',','')){
						$change_product = true;
						$Item->setSpecial_price($special_price_ht);
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de prix spécial ont été sauvegardée'));
	        }
        }

		/*******Gestion des prix TTC produits********/
		$data = $this->getRequest()->getPost('price_ttc');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $price_ttc) {
				$Item = Mage::getModel('catalog/product')->load($productId);
				if ($Item) {

					// Get the product's tax class' ID
					$taxClassId = $Item->getData("tax_class_id");
					// Get the tax rates of each tax class in an associative array
					$taxClasses = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass());
					// Extract the tax rate from the array
					$taxRate = $taxClasses["value_".$taxClassId];

					$price_ht = (float)str_replace(',','.',$price_ttc) / (($taxRate/100) +1);

					if(number_format($Item->getPrice(),4,',','') != number_format($price_ht,4,',','')){
						$change_product = true;
						$Item->setPrice($price_ht);
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de prix TTC ont été sauvegardée'));
	        }
        }


		/*******Gestion des prix Revendeurs produits********/
		$data = $this->getRequest()->getPost('price_revendeur');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $price_revendeur) {

				$Item = Mage::getModel('catalog/product')->load($productId);
				if ($Item) {
					$i=0;
					foreach($Item->group_price as $groupprice){
						if($groupprice['cust_group'] == 3){
							$priceCourant = $groupprice['price'];
							break;
						}
					}
					if(number_format($priceCourant,2,',','') != $price_revendeur){
						$change_product = true;
						$Item->setGroup_price(array(array(
													"website_id" => 0,
													"cust_group" => 3,
													"price" => $price_revendeur,
												)));
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de prix revendeurs ont été sauvegardée'));
	        }
        }

		/*******Gestion des provider********/
		$data = $this->getRequest()->getPost('provider');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $provider) {
				$Item = Mage::getModel('catalog/product')->load($productId);
				if ($Item) {
					if($provider != $Item->provider){
						$change_product = true;
						$Item->setProvider($provider);
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de fournisseurs ont été sauvegardée'));
	        }
        }

		/*******Gestion des date********/
		$data = $this->getRequest()->getPost('date_create');
        $errors = array();
        if (count($data)>0) {
			$change_product = false;
	        foreach ($data as $productId => $date_create) {
				$Item = Mage::getModel('catalog/product')->load($productId);
				if ($Item) {
					if(strtotime($date_create) != strtotime(date('d-m-Y',strtotime($Item->getData("created_at"))))){
						$change_product = true;
						$Item->setCreated_at(date('Y-m-d H:i:s',strtotime($date_create)));
						$Item->save();
					}
				}
	        }

	        if (count($errors) == 0 && $change_product) {
	            $this->_getSession()->addSuccess($this->__('Les modifications de date de création ont été sauvegardée'));
	        }
        }

        // Ré-indexation manuelle des prix
        // (fix d'un bug bizarre où la ré-indexation semble être faite alors qu'en fait non)
        $process = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price');
        $process->reindexAll();

        $this->_redirectReferer();
    }

	public function exportXmlAction()
    {
        $fileName   = 'grid-prix-produits.xml';
        $content    = $this->getLayout()->createBlock('sdw_produitsedit_block_adminhtml_listing_grid')->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

	public function exportCsvAction()
    {
		$fileName   = 'grid-prix-produits.csv';
        $content    = $this->getLayout()->createBlock('sdw_produitsedit_block_adminhtml_listing_grid')->getCsv();
        $this->_sendUploadResponse($fileName, $content);
    }

	public function exportCsvMargeAction()
    {
		$fileName   = 'grid-marge-produits.csv';
        $content    = $this->getLayout()->createBlock('sdw_produitsedit_block_adminhtml_listing_grid')->getCsvMarge();
        $this->_sendUploadResponse($fileName, $content);
    }

	protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}

