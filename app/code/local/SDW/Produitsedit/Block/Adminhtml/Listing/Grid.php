<?php
class SDW_Produitsedit_Block_Adminhtml_Listing_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct( )
	{
		parent::__construct( );
		$this->setId( 'productEditGrid' );
		$this->setDefaultSort( 'entity_id' );
		$this->setDefaultDir( 'DESC' );
		$this->setSaveParametersInSession( true );
		$this->setUseAjax( false );
		$this->setVarNameFilter( 'product_filter' );
		$this->setTemplate( 'produitsedit/grid.phtml' );
	}

	protected function _getStore( )
	{
		$storeId = (int) $this->getRequest( )->getParam( 'store', 0 );
		return Mage::app( )->getStore( $storeId );
	}

	protected function _prepareCollection( )
	{
		$store = $this->_getStore( );
		$collection = Mage::getModel( 'catalog/product' )->getCollection( )->addAttributeToSelect( 'sku' )->addAttributeToSelect( 'provider' )->addAttributeToSelect( 'name' )->addAttributeToSelect( 'weight' )->addAttributeToSelect( 'ref_fournisseur' )->addAttributeToSelect( 'code_barre' )->addAttributeToSelect( 'special_price' );
		if( Mage::helper( 'catalog' )->isModuleEnabled( 'Mage_CatalogInventory' ) )
		{
			$collection->joinField( 'qty', 'cataloginventory/stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left' );
		}
		if( $store->getId( ) )
		{
			$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
			$collection->addStoreFilter( $store );
			$collection->joinAttribute( 'name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore );
			$collection->joinAttribute( 'custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId( ) );
			$collection->joinAttribute( 'status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId( ) );
			$collection->joinAttribute( 'visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId( ) );
			$collection->joinAttribute( 'price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId( ) );
		}
		else
		{
			$collection->addAttributeToSelect( 'price' );
			$collection->joinAttribute( 'status', 'catalog_product/status', 'entity_id', null, 'inner' );
			$collection->joinAttribute( 'visibility', 'catalog_product/visibility', 'entity_id', null, 'inner' );
		}
		$collection->getSelect( )->where( 'e.type_id = ?', "simple" );
		$this->setCollection( $collection );
		parent::_prepareCollection( );
		$this->getCollection( )->addWebsiteNamesToResult( );
		return $this;
	}

	private function orderbyCode($a,$b){
		return strcmp($a, $b);
	}

	protected function _prepareColumns( )
	{
		$store = $this->_getStore();
		$collection = Mage::getModel('provider/provider')->getCollection();

		$transporteur = array();
		foreach($collection as $value){
			$data = $value->getData();
			$transporteur[$data['id_provider']] = $data['code'].'-'.$data['sociale'];
		}
		uasort($transporteur, array('SDW_Produitsedit_Block_Adminhtml_Listing_Grid','orderbyCode'));
		
        $this->addColumn( 'status', array( 'header' => Mage::helper( 'catalog' )->__( 'Statut' ), 'index' => 'status', ) );
        $this->addColumn( 'name', array( 'header' => Mage::helper( 'catalog' )->__( 'Designation' ), 'index' => 'name', ) );
		$this->addColumn( 'qty', array( 'header' => Mage::helper( 'catalog' )->__( 'Qty' ), 'index' => 'qty','type' => 'number','filter'    => false, ) );
		$this->addColumn( 'code_barre', array( 'header' => Mage::helper( 'catalog' )->__( 'Code' ), 'index' => 'code_barre' ) );
		$this->addColumn( 'weight', array( 'header' => Mage::helper( 'catalog' )->__( 'Weight' ), 'index' => 'weight','type' => 'number','filter'  => false, ) );

		//edit
		$this->addColumn( 'sku', array( 'header' => Mage::helper( 'catalog' )->__( 'RefC' ), 'width' => 80, 'index' => 'sku', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Sku', ) );

		//edit
		$this->addColumn( 'ref_fournisseur', array( 'header' => Mage::helper( 'catalog' )->__( 'RefF' ), 'width' => 80, 'index' => 'ref_fournisseur', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Reffournisseur', ) );

		//edit
        $this->addColumn('prix_achat_dollar', array(
            'header'    => Mage::helper('catalog')->__('PA $'),
            'width'     => 100,
			'type' 		=> 'price',
            'index'     => 'prix_achat_dollar',
			'renderer'  => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Renderer',
			'filter'    => false,
            'sortable'  => false,
        ));
		
		//edit
		$this->addColumn( 'prix_achat', array( 'header' => Mage::helper( 'catalog' )->__( 'P€' ), 'width' => 100, 'type' => 'price', 'index' => 'prix_achat', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Achat', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'cout_transport', array( 'header' => Mage::helper( 'catalog' )->__( 'T' ), 'type' => 'price', 'width' => 80, 'index' => 'cout_transport', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Couttransport', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'price_r', array( 'header' => Mage::helper( 'catalog' )->__( 'PR' ), 'type' => 'price', 'width' => 80, 'index' => 'price_r', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Pricerevient', 'filter' => false, 'sortable' => false, ) );

		//edit
		$this->addColumn( 'special_price_ht', array( 'header' => Mage::helper( 'catalog' )->__( 'PVHT' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'special_price_ht', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Specialht', 'filter' => false, 'sortable' => false, ) );

		//edit
		$this->addColumn( 'special_price_ttc', array( 'header' => Mage::helper( 'catalog' )->__( 'PVTTC' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'special_price_ttc', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Specialttc', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'marge_brute', array( 'header' => Mage::helper( 'catalog' )->__( 'MB' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'marge_brute', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Marge', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'taux_marge_brute', array( 'header' => Mage::helper( 'catalog' )->__( '% MB' ), 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'taux_marge_brute', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Tauxmarge', 'filter' => false, 'sortable' => false, ) );

		$this->addColumn( 'price_ttc', array( 'header' => Mage::helper( 'catalog' )->__( 'PI' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'price_ttc', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Pricettc', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'taux_remise', array( 'header' => Mage::helper( 'catalog' )->__( '% PI' ), 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'taux_remise', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Tauxremise', 'filter' => false, 'sortable' => false, ) );

		//edit
		$this->addColumn( 'revendeur', array( 'header' => Mage::helper( 'catalog' )->__( 'PVR' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'revendeur', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Revendeur', 'filter' => false, 'sortable' => false, ) );
		$this->addColumn( 'marge_brute_revendeur', array( 'header' => Mage::helper( 'catalog' )->__( '% MBR' ), 'type' => 'price', 'width' => 80, 'currency_code' => $store->getBaseCurrency( )->getCode( ), 'index' => 'marge_brute_revendeur', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Margerevendeur', 'filter' => false, 'sortable' => false, ) );

		//edit
		$this->addColumn('provider', array(
            'header'    => Mage::helper('catalog')->__('Fourn'),
            'index'     => 'provider',
			'type'  	=> 'options',
			'renderer'  => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Provider',
			'options'    => $transporteur		
        ));
		
		$this->addColumn( 'created_at', array( 'header' => Mage::helper( 'catalog' )->__( 'Date' ), 'type' => 'date', 'index' => 'created_at', 'renderer' => 'SDW_Produitsedit_Block_Adminhtml_Listing_Render_Createat', 'filter' => false, 'sortable' => false, ) );

		$this->addExportType('*/*/exportCsv', $this->__('CSV'));	

		$this->addExportType('*/*/exportCsvMarge', $this->__('CSV Stock'));	
		
		return parent::_prepareColumns( );
	}

	public function getRowUrl( $row )
	{
		return false;
	}
	
	private function addInfoCsv($texte)
    {
		$texte = str_replace('"','""',$texte);
       return '"'.$texte.'"';
    }
	
	public function getCsv()
	{
		$csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
		$this->getCollection()->clear();
		
        $this->getCollection()->getSelect()->limit(0);
        $this->getCollection()->setPageSize(false);
        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $csv.= '"Designation";"RefC";"RefF";"PA";"T";"PR";"PVHT";"PVTTC";"MB";"%MB";"PI";"% PI";"PVR";"% MBR";"Fourn";"Date";"Quantité";"Code barre";"Poids"'."\n";

		$i=0;
        foreach ($this->getCollection() as $item) {
			$_product = Mage::getModel('catalog/product')->load($item->entity_id);

			$providerModel = Mage::getModel('provider/provider')->load((int)$item->provider);		
			$data = $providerModel->getData();
			
			if(!$item->provider) $price_revient = $_product->getData('prix_achat');
			else $price_revient = $_product->getData('prix_achat') + ($_product->getData('prix_achat') * $data['transport'] / 100);
			
			if($_product->special_price == NULL)$price = $_product->getPrice();
			else $price = $_product->special_price;
			
			$specialttc = Mage::helper('tax')->getPrice($_product, $price);
			$pricettc = Mage::helper('tax')->getPrice($_product, $_product->getPrice());
			
			$cust_group = '';
			foreach($_product->group_price as $groupprice){
				if($groupprice['cust_group'] == 3){
					$cust_group = number_format($groupprice['price'],2,'.','');
					break;
				}
			}
			
            $csv.= $this->addInfoCsv($item->name).";".
			$this->addInfoCsv($item->sku).";".
			$this->addInfoCsv($item->ref_fournisseur).";".
			$this->addInfoCsv(number_format($_product->getData('prix_achat'),2,'.','')).";".
			$this->addInfoCsv(number_format($_product->getData('prix_achat') * $data['transport'] / 100, 2,'.','')).";".
			$this->addInfoCsv(number_format($price_revient, 2,'.','')).";".
			$this->addInfoCsv(number_format($price  ,2,'.','')).";".
			$this->addInfoCsv(number_format($specialttc  ,2,'.','')).";".
			$this->addInfoCsv(number_format($price - $price_revient, 2,'.','')).";".
			$this->addInfoCsv(number_format((($price - $price_revient)/ $price) * 100, 1,'.','')."%"). ";".
			$this->addInfoCsv(number_format($pricettc,2,'.','')). ";".
			$this->addInfoCsv(number_format(100-(($specialttc * 100) / $pricettc),0,'.','')."%"). ";".
			$this->addInfoCsv(number_format($cust_group,2,'.','')). ";".
			$this->addInfoCsv(number_format((($cust_group - $price_revient ) / $cust_group) * 100, 1,'.','')."%"). ";".
			$this->addInfoCsv($data['code'].'-'.$data['sociale']). ";".
			$this->addInfoCsv(date('d-m-Y',strtotime($_product->getData('created_at')))).";".
			$this->addInfoCsv(number_format($_product->getStockItem()->getQty(),2,'.','')).";".
			$this->addInfoCsv($_product->getCodeBarre()).";".
			$this->addInfoCsv(number_format($_product->getWeight(),2,'.','')).
			"\n";
        }
        return $csv;
	}
	
	public function getCsvMarge()
	{
		$csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
		$this->getCollection()->clear();
		
        $this->getCollection()->getSelect()->limit(0);
        $this->getCollection()->setPageSize(false);
        $this->getCollection()->load();
        $this->_afterLoadCollection();
		
		$visibility = Mage::getModel('catalog/product_visibility')->getOptionArray();
		
		$this->getCollection()->joinField('manages_stock','cataloginventory/stock_item','use_config_manage_stock','product_id=entity_id','{{table}}.manage_stock=1');

        $csv.= '"Designation";"RefC";"Qty";"Prix Achat";"taux transporteur";"Prix Revient";"Prix Vente HT";"Marge Brute";"% Marge Brute";"Status";"Manages stock";"Visibilité"'."\n";

		$i=0;
		$totalQuantity += 0;
		$totalPrixAchat += 0;
		$totalPrixRevient += 0;
		$totalMarge += 0;
        foreach ($this->getCollection() as $item) {
			$_product = Mage::getModel('catalog/product')->load($item->entity_id);

			$providerModel = Mage::getModel('provider/provider')->load((int)$item->provider);		
			$data = $providerModel->getData();
			
			if(!$item->provider) $price_revient = $_product->getData('prix_achat');
			else $price_revient = $_product->getData('prix_achat') + ($_product->getData('prix_achat') * $data['transport'] / 100);
			
			if($_product->special_price == NULL)$price = $_product->getPrice();
			else $price = $_product->special_price;
			
			$specialttc = Mage::helper('tax')->getPrice($_product, $price);
			$pricettc = Mage::helper('tax')->getPrice($_product, $_product->getPrice());
			
			$cust_group = '';
			foreach($_product->group_price as $groupprice){
				if($groupprice['cust_group'] == 3){
					$cust_group = number_format($groupprice['price'],2,'.','');
					break;
				}
			}
			
			
			$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($_product);		
			$quantity = number_format($stockItem->getQty(),0,',',''); 
			$prixAchat = number_format($_product->getData('prix_achat'),4,',','');
			$prixRevient = number_format($price_revient, 2,',','');
			$marge = number_format($price - $price_revient, 2,',','');
			
			if($_product->status == 1){
				$status = utf8_decode('Activé');
			}
			else $status = utf8_decode('Désactivé');

		
			if($stockItem->getData('manage_stock') == 1){
				$manages_stock = utf8_decode('Activé');
			}
			else $manages_stock = utf8_decode('Désactivé');
			

            $csv.= $this->addInfoCsv($item->name).";".
            $this->addInfoCsv($item->sku).";".
			$this->addInfoCsv($quantity).";".
			$this->addInfoCsv($prixAchat).";".
			$this->addInfoCsv($data['transport'])."%;".
			$this->addInfoCsv($prixRevient).";".
			$this->addInfoCsv(number_format($price  ,2,',','')).";".
			$this->addInfoCsv($marge).";".
			$this->addInfoCsv(number_format((($price - $price_revient)/ $price) * 100, 1,',','')."%"). ";".
			$this->addInfoCsv($status). ";".
			$this->addInfoCsv($manages_stock). ";".
			$this->addInfoCsv($visibility[$item->visibility]). 
			"\n";
			
			$totalQuantity += $quantity;
			$totalPrixAchat += $prixAchat;
			$totalPrixRevient += $prixRevient;
			$totalMarge += $marge;
			

        }

		//$csv.= "\n\n TOTAL ; ".$totalQuantity.";".$totalPrixAchat.";;".$totalPrixRevient.";".$totalMarge.";\n";
		
        return $csv;
	}
}
