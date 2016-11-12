<?php

class SDW_Provider_Model_Pdf_Provider extends Mage_Sales_Model_Order_Pdf_Abstract
{
	public function getPdf($providerOrder = array(), $provider = array())
	{
		$order_id =(int)$providerOrder[0]['provider_order_id'];
		
		$this->_beforeGetPdf();
		$this->_initRenderer('invoice');

		$pdf = new Zend_Pdf();
		$this->_setPdf($pdf);
		$style = new Zend_Pdf_Style();
		$this->_setFontBold($style, 10);

		
		$page = $pdf->newPage(Zend_Pdf_Page::SIZE_A4);
		$pdf->pages[] = $page;

		/* Add address */
		$this->insertAddress($page, $providerOrder[0], $provider);
		
		// Footer
		$this->_setFontRegular($page, 6);
		$page->setFillColor(new Zend_Pdf_Color_Html('#FFA614'));
		$page->drawText('POWELL - www.colony.fr - Les Augers - 78980 Saint Illiers La ville - France ', 190, 25, 'UTF-8');
		$this->_setFontRegular($page, 6);
		$page->drawText('Siret 488 254 798 00025 - TVA FR 95488254798 - Capital 264 300 euros - Tél 00 33 1 34 76 06 04 - Fax 00 33 1 34 76 14 39', 145, 15, 'UTF-8');

		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		$this->_setFontBold($page, 10);
		$page->drawText('Commande N° : '.$order_id, 30, $this->y, 'UTF-8');
		
		$this->y += -30;
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

		/* Add table */
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
		$page->setLineWidth(0.5);
		$this->y +=10;
		$page->drawRectangle(25, $this->y, 570, $this->y -15);
		$this->y -=11;

		/* Add table head */		
		$this->_setFontRegular($page, 10);
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		$page->drawText('Désignation :', 30, $this->y, 'UTF-8');
		$page->drawText('Ref C :', 220, $this->y, 'UTF-8');
		$page->drawText('Ref F :', 300, $this->y, 'UTF-8');
		$page->drawText('Qty :', 380, $this->y, 'UTF-8');
		$page->drawText('PA :', 450, $this->y, 'UTF-8');
		$page->drawText('Total', 540, $this->y, 'UTF-8');

		// Haut et bas du tableau
		$hy = $this->y-5;
		
		$by = 45;

		$this->y -= 15;

		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		
		$first = true;
		
		$nb_produit 	= 0;
		$nb_colis		= 0;
		$price_total 	= 0;
		$volume_total 	= 0;

		foreach($providerOrder as $product)
		{
			if(!$first)$page->drawLine(25, $this->y + 10 , 570,  $this->y + 10 );
			$item = Mage::getModel('catalog/product')->load($product['product_id']);
			$dataItem = $item->getData();

			if ($this->y + $dy < $by) {
				$page->drawLine(25, $hy, 25, $by);
				$page->drawLine(210, $hy, 210, $by);
				$page->drawLine(290, $hy, 290, $by);
				$page->drawLine(370, $hy, 370, $by);
				$page->drawLine(440, $hy, 440, $by);
				$page->drawLine(530, $hy, 530, $by);
				$page->drawLine(570, $hy, 570, $by);
				$page->drawLine(25, $by, 570, $by);
				$hy=795;
				$page = $this->newPage(array('table_header' => true));
			}
			
			$lines  = array();

			// draw Product name
			$lines[0] = array(array(
				'text' => Mage::helper('core/string')->str_split($item->getName(), 60, true, true),
				'feed' => 40,
				'font_size' => 6
			));
			
			// draw SKU
			$lines[0][] = array(
				'text'  => $dataItem['sku'],
				'feed'  => 230,
				'font_size' => 6
			);
			
			// draw Ref fournisseur
			$lines[0][] = array(
				'text'  => $dataItem['ref_fournisseur'],
				'feed'  => 310,
				'font_size' => 6
			);
			
			// draw QTY
			$lines[0][] = array(
				'text'  => $product['quantity'],
				'feed'  => 390,
				'align' => 'right',
				'font_size' => 6
			);

			$fromCurrency = 'EUR'; // currency code to convert from - usually your base currency  
			$toCurrency = $product['monnaie']; // currency to convert to 
			
			if($toCurrency == 'USD'){
				if($dataItem['prix_achat_dollar'] != '')
					$price = $dataItem['prix_achat_dollar'];
				else 
					$price = Mage::helper('directory')->currencyConvert($dataItem['prix_achat'], $fromCurrency, $toCurrency);
			}
			else $price = $dataItem['prix_achat'];
			
			// if you want it rounded:  
			$final_price = Mage::app()->getStore()->roundPrice($price); 
			
			// draw Price Achat
			$lines[0][] = array(
				'text'  => number_format($final_price,2,',','').' '.$toCurrency,
				'feed'  => 480,
				'align' => 'right',
				'font_size' => 6
			);

			/*
			// draw Price Unitaire
			$lines[0][] = array(
				'text'  => $final_price .' '.$toCurrency,
				'feed'  => 495,
				'align' => 'right',
				'font_size' => 6
			);
			*/

			// draw Price Total
			$lines[0][] = array(
				'text'  => number_format($final_price * $product['quantity'],2,',','').' '.$toCurrency,
				'feed'  => 560,
				'align' => 'right',
				'font_size' => 6
			);


			$lineBlock = array(
				'lines'  => $lines,
				'height' => 15
			);

			$page = $this->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
			$this->setPage($page);
			
			$first = false;
			
			$colis = explode("\n",$item->shipping_sku);
			
			$nb_produit 	+= $product['quantity'];
			$nb_colis 		+= count($colis);
			$volume_total 	+= $item->getWeight();
			$price_total 	+= $final_price * $product['quantity'];
		}
	
		$by = $this->y + 10;
		
		// Lines
		$page->drawLine(25, $hy+16, 25, $by);
		$page->drawLine(210, $hy+16, 210, $by);
		$page->drawLine(290, $hy+16, 290, $by);
		$page->drawLine(370, $hy+16, 370, $by);
		$page->drawLine(440, $hy+16, 440, $by);
		$page->drawLine(530, $hy+16, 530, $by);
		$page->drawLine(570, $hy+16, 570, $by);
		$page->drawLine(25, $by, 570, $by);
		
		/* Add totals */
        $this->insertTotals($page, $price_total, $nb_colis, $nb_produit, $volume_total, $toCurrency);

		$this->_afterGetPdf();
 
		return $pdf;
	}

	public function newPage(array $settings = array())
	{
		/* Add new table head */
		$page = $this->_getPdf()->newPage(Zend_Pdf_Page::SIZE_A4);
		$this->_getPdf()->pages[] = $page;
		$this->y = 800;

		if (!empty($settings['table_header'])) {
			/* Add table */
			$this->_setFontBold($page, 10);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(1));

			/* Add table */
			$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
			$page->setLineWidth(0.5);
			$this->y +=12;
			$page->drawRectangle(25, $this->y, 570, $this->y -15);
			$this->y -=11;

			/* Add table head */		
			$this->_setFontRegular($page, 10);
			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
			$page->drawText('Désignation :', 30, $this->y, 'UTF-8');
			$page->drawText('Ref C :', 220, $this->y, 'UTF-8');
			$page->drawText('Ref F :', 300, $this->y, 'UTF-8');
			$page->drawText('Qty :', 380, $this->y, 'UTF-8');
			$page->drawText('PA :', 450, $this->y, 'UTF-8');
			$page->drawText('Total', 540, $this->y, 'UTF-8');


			$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
			$this->y -=20;
		}

		// Footer
		$this->_setFontRegular($page, 6);
		$page->setFillColor(new Zend_Pdf_Color_Html('#FFA614'));
		$page->drawText('POWELL - www.colony.fr - Les Augers - 78980 Saint Illiers La ville - France ', 190, 25, 'UTF-8');
		$this->_setFontRegular($page, 6);
		$page->drawText('Siret 488 254 798 00025 - TVA FR 95488254798 - Capital 186 300 euros - Tél 00 33 1 34 76 06 04 - Fax 00 33 1 34 76 14 39', 145, 15, 'UTF-8');
		
		$this->_setFontRegular($page, 10);
		$page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));

		return $page;
	}

	protected function _setFontRegular($object, $size = 7)
	{
		$font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/skin/frontend/colony/default/fonts/Myriad-Pro.ttf');
		$object->setFont($font, $size);
		return $font;
	}

	protected function _setFontBold($object, $size = 7)
	{
		$font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir() . '/skin/frontend/colony/default/fonts/Myriad-Pro-Bold.ttf');
		$object->setFont($font, $size);
		return $font;
	}

	protected function insertAddress(&$page, $providerOrder, $provider)
	{		
		$order_id =(int)$providerOrder['provider_order_id'];

		$create = strtotime($providerOrder['order_create']);
		$livraison = $create + ($provider['delai'] * 7 * 24 * 60 * 60);		
		
		$this->y = 800;
		$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
		$page->setFillColor(new Zend_Pdf_Color_Html('#7BAE00'));
		$this->_setFontBold($page, 40);
		$page->drawText(strtoupper($provider['sociale']), 30, $this->y, 'UTF-8'); $this->y -=15;
		$this->_setFontRegular($page, 12);	
		$page->drawText('Code : '.$provider['code'], 30, $this->y, 'UTF-8'); $this->y -=15;			
		if(!empty($provider['adresse1'])){$page->drawText($provider['adresse1'], 30, $this->y, 'UTF-8'); $this->y -=15;}
		if(!empty($provider['adresse2'])){$page->drawText($provider['adresse2'], 30, $this->y, 'UTF-8'); $this->y -=15;}
		if(!empty($provider['adresse3'])){$page->drawText($provider['adresse3'], 30, $this->y, 'UTF-8'); $this->y -=15;}
		$page->drawText($provider['cp']." ".$provider['ville']." - ".$provider['pays'], 30, $this->y, 'UTF-8'); $this->y -=15;
		if(!empty($provider['tel1'])){$page->drawText("Tél : ".$provider['tel1'], 30, $this->y, 'UTF-8'); $this->y -=15;}		
		if(!empty($provider['tel2'])){$page->drawText("Tél : ".$provider['tel2'], 30, $this->y, 'UTF-8'); $this->y -=15;}		
		if(!empty($provider['email'])){$page->drawText("Email : ".$provider['email'], 30, $this->y, 'UTF-8'); $this->y -=15;}
		$page->drawText("Date de livraison prévue : ".Mage::getModel('core/date')->date('d M. Y' , $livraison), 30, $this->y, 'UTF-8'); $this->y -=15;
		$page->drawText("Date de bon de commande : ".Mage::getModel('core/date')->date('d M. Y' , $create), 30, $this->y, 'UTF-8'); $this->y -=15;
	}
	
	protected function insertTotals($page, $price_total, $nb_colis, $nb_produit, $volume_total, $toCurrency){
        $lineBlock = array(
            'lines'  => array(),
            'height' => 15
        );
			
		$lineBlock['lines'][] = array(
			array(
				'text'      => "Total",
				'feed'      => 475,
				'align'     => 'right',
				'font'      => 'bold'
			),
			array(
				'text'      => number_format($price_total,2,',','').' '.$toCurrency,
				'feed'      => 565,
				'align'     => 'right',
				'font'      => 'bold'
			),
		);	
		
		$lineBlock['lines'][] = array(
			array(
				'text'      => "Nbre de colis",
				'feed'      => 475,
				'align'     => 'right',
				'font'      => 'bold'
			),
			array(
				'text'      => $nb_colis,
				'feed'      => 565,
				'align'     => 'right',
				'font'      => 'bold'
			),
		);	
		
		$lineBlock['lines'][] = array(
			array(
				'text'      => "Nbre Produit",
				'feed'      => 475,
				'align'     => 'right',
				'font'      => 'bold'
			),
			array(
				'text'      => $nb_produit,
				'feed'      => 565,
				'align'     => 'right',
				'font'      => 'bold'
			),
		);	
		
		$lineBlock['lines'][] = array(
			array(
				'text'      => "Volume Total (m3)",
				'feed'      => 475,
				'align'     => 'right',
				'font'      => 'bold'
			),
			array(
				'text'      => $volume_total,
				'feed'      => 565,
				'align'     => 'right',
				'font'      => 'bold'
			),
		);

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, array($lineBlock));
		
        return $page;
    }
}