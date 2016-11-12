<?php
class SDW_Importprix_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction()
	{
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));
		return $this;
	}

	public function indexAction()
	{
		$this->loadLayout();
		$this->_title($this->__("Importer prix produit"));
		$block = $this->getLayout()->createBlock('core/template')->setTemplate("importprix/formulaire.phtml");
		$this->getLayout()->getBlock('content')->append($block);
		$this->renderLayout();
	}

	public function importAction()
	{
		$hasError=false;
		$hasWarning=false;

		$colonne_refc=$_POST['colonne_refc'];
		$colonne_pvttc=$_POST['colonne_pvttc'];
		$colonne_pi=$_POST['colonne_pi'];
		$colonne_poids=$_POST['colonne_poids'];
		$ignore_first=$_POST['ignore_first'];
		$separateur_csv=$_POST['separateur_csv'];



		
		if($ignore_first!="oui" && $ignore_first!="non")
		{
			$hasError=true;
			Mage::getSingleton("adminhtml/session")->addError("Le champ «La première ligne contient les titres» est incorrect.");
		}

		if($separateur_csv!=";" && $separateur_csv!=",")
		{
			$hasError=true;
			Mage::getSingleton("adminhtml/session")->addError("Le champ «Séparateur csv» est incorrect.");
		}


		if(!isset($_FILES['file']) || $_FILES['file']['error']!=0)
		{
			$hasError=true;
			Mage::getSingleton("adminhtml/session")->addError("Le fichier est requis.");
		}
		else if($_FILES['file']['type']=!"text/csv")
		{
			$hasError=true;
			Mage::getSingleton("adminhtml/session")->addError("Le fichier n'est pas un fichier csv correct.");
		}

	
		if(!$hasError)
		{
			$csv=fopen($_FILES['file']['tmp_name'],"r");
			if($ignore_first && $ignore_first!="non")fgetcsv($csv,0,$separateur_csv);
			
			while($row=fgetcsv($csv,0,$separateur_csv))
			{
				if($colonne_refc!="" && !isset($row[$colonne_refc]))
				{
					$hasWarning=true;
					continue;
				}
				else if($colonne_pvttc!="" && !isset($row[$colonne_pvttc]))
				{
					$hasWarning=true;
					continue;
				}
				else if($colonne_pi!="" && !isset($row[$colonne_pi]))
				{
					$hasWarning=true;
					continue;
				}
				else if($colonne_poids!="" && !isset($row[$colonne_poids]))
				{
					$hasWarning=true;
					continue;
				}

				foreach(Mage::getResourceModel('customer/group_collection') as $group)
				{
					if($group->getId()<3)continue;

					$colonne_pvr=$_POST['colonne_pvr_'.$group->getId()];
					if($colonne_pvr!="" && !isset($row[$colonne_pvr]))
					{
						$hasWarning=true;
						continue 2;
					}
				}
				

				$refc=$row[$colonne_refc];
				$pvttc=$row[$colonne_pvttc];
				$pi=$row[$colonne_pi];
				$pvr=$row[$colonne_pvr];
				$poid=$row[$colonne_poids];

				$product=Mage::getModel('catalog/product')->loadByAttribute('sku',$refc);
				if($product)$product=Mage::getModel('catalog/product')->load($product->getId());
				if(!$product)
				{
					Mage::getSingleton("adminhtml/session")->addError("Aucun produit n'existe pour la référence «${refc}»");
					continue;
				}

				$taxClassId = $product->getData("tax_class_id");
				$taxClasses = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass());
				$taxRate = $taxClasses["value_".$taxClassId];

				if($colonne_pvttc!="")
				{
					// PVTTC
					$special_price_ht = (float)str_replace(',','.',$pvttc) / (($taxRate/100) +1);
					if(number_format($product->special_price,4,',','') != number_format($special_price_ht,4,',','') && number_format($special_price_ht,4,',','') != number_format($product->getFinalPrice(),4,',',''))
						$product->setSpecial_price($special_price_ht);
				}

				if($colonne_pi!="")
				{
					// PI
					$price_ht = (float)str_replace(',','.',$pi) / (($taxRate/100) +1);
					if(number_format($product->getPrice(),4,',','') != number_format($price_ht,4,',',''))
						$product->setPrice($price_ht);
				}

				
				$prices=array();
				foreach($product->group_price as $price)
				{
					$prices[$price["cust_group"]."/".$price["website_id"]]=$price;
				}

				foreach(Mage::getResourceModel('customer/group_collection') as $group)
				{
					if($group->getId()<3)continue;

					$colonne_pvr=$_POST['colonne_pvr_'.$group->getId()];
					if($colonne_pvr!="")
					{
						if(!isset($prices[$group->getId()."/0"]))
						{
							$prices[$group->getId()."/0"]=array(
								"website_id" => 0,
								"cust_group" => $group->getId(),
								"price"=> $row[$colonne_pvr]
							);
						}
						else
						{
							$prices[$group->getId()."/0"]["price"]=$row[$colonne_pvr];
						}
					}
				}

				$product->setGroupPrice(array_values($prices));
				
				if($colonne_poids!="")
				{
					// Poid
					$product->setWeight($poid);
				}

				$product->save();
			}

			if($hasWarning)
			{
				Mage::getSingleton("adminhtml/session")->addError("Les colonnes n'existaient pas pour certains produits, l'import a été ignoré pour ces produits.");
			}

			Mage::getSingleton("adminhtml/session")->addSuccess("L'import est terminé.");

		}

		$this->_redirect('*/*/');
	}
}

