<?php

class SDW_Produitsedit_Block_Adminhtml_Listing_Render_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row)
	{
		$data = $this->loadData($row);
		return $data[$this->getColumn()->getIndex()];
	}


	public function loadData($row)
	{	
		$product 	= Mage::getModel('catalog/product')->load($row->entity_id);
		
		$provider = Mage::getModel('provider/provider')->load($row->provider);

		

		if($provider['monnaie'] == 'USD'){	
			$rates = Mage::getModel('directory/currency')->getCurrencyRates('EUR', 'USD');
			if($product->getData('prix_achat_dollar')!='')
				$priceDollarsTemp = number_format($product->getData('prix_achat_dollar'),2,',',''); 
			else
				$priceDollarsTemp = number_format(Mage::helper('directory')->currencyConvert($product->getData('prix_achat'), 'EUR', 'USD'),2,',',''); // Mise en place de l'ancienne valeur pour initialisation.
			$priceDollars = '<input type="text" onblur="updateDollars('.$row->getId().');updateAchat('.$row->getId().')" data-convert="'.(1/$rates['USD']).'" id="prix_achat_dollar_'.$row->getId().'" class="product-price-input input-text validate-number" name="prix_achat_dollar['.$row->getId().']" value="'.$priceDollarsTemp.'"/>  $'; 
		
			if($product->getData('prix_achat_dollar')!=''){
				$priceEurosTemp = number_format($product->getData('prix_achat_dollar') * (1/$rates['USD']) ,2,',','');
			}
			else
				$priceEurosTemp = number_format($product->getData('prix_achat'),2,',',''); // Mise en place de l'ancienne valeur pour initialisation.
			$priceEuros = '<input type="text" id="prix_achat_'.$row->getId().'" class="product-price-input input-text validate-number" name="prix_achat['.$row->getId().']" value="'.$priceEurosTemp.'" readonly="readonly" disabled="disabled"/> €';
		}
		else {
			$rates = Mage::getModel('directory/currency')->getCurrencyRates('EUR', 'USD');
			$priceEuros = '<input type="text" onblur="updateEuros('.$row->getId().');updateAchat('.$row->getId().')" data-convert="'.$rates['USD'].'" id="prix_achat_'.$row->getId().'" class="product-price-input input-text validate-number" name="prix_achat['.$row->getId().']" value="'.number_format($product->getData('prix_achat'),2,',','').'"/> €';

			$priceDollarsTemp = number_format(Mage::helper('directory')->currencyConvert($product->getData('prix_achat'), 'EUR', 'USD'),2,',',''); 			
			$priceDollars = '<input type="text" id="prix_achat_dollar_'.$row->getId().'" class="product-price-input input-text validate-number" name="prix_achat_dollar['.$row->getId().']" value="'.$priceDollarsTemp.'" readonly="readonly" disabled="disabled"/> $';
		}

		
		return array(
			"prix_achat" 			=> $priceEuros,
			"prix_achat_dollar" 	=> $priceDollars,
		);
	}
}