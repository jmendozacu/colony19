<?php
class SDW_Provider_Block_Adminhtml_Listing_Edit_Tab_Status extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
   {
       parent::__construct();
       $this->setId('providerCommandeGrid');
       $this->setDefaultSort('provider_order_id');
       $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);
   }
   protected function _prepareCollection()
   {
        $collection = Mage::getModel('provider/provider')->getCollection();
		$collection->getSelect()->reset('columns');  

	//	$collection->joinAttribute('name', 'catalog_product/name', 'entity_id', null, 'inner', $adminStore);
					
		$collection->getSelect()->join(array('provider_order' => 'mgt_sdw_provider_order'),
										'provider_order.id_provider = main_table.id_provider',
										array('id_provider_order'=>'id_provider_order',
											'provider_order_id'=>'provider_order_id',
											'order_create'=>'order_create',
											'product_id'=>'product_id',
											'quantity'=>'quantity',
											'status'=>'status'));	

		$collection->getSelect()->where('provider_order.id_provider = ?', (int)$this->getRequest()->getParam('id'));
		$collection->getSelect()->group(array('provider_order_id'));

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

	protected function _prepareColumns()
	{	
		/*
		$this->addColumn('id_provider_order',
             array(
                    'header' => Mage::helper('catalog')->__('ID'),
                    'align' =>'right',
					'width' => 50,
					'type'  => 'number',
                    'index' => 'id_provider_order',
               ));*/
	
		$this->addColumn('provider_order_id',
             array(
                    'header' => Mage::helper('catalog')->__('N° de commande'),
                    'align' =>'right',
					'width' => 50,
                    'index' => 'provider_order_id',
               ));

		$this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
            'index'     => 'name',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Rendererstatus',
        ));
			
		$this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => 80,
            'index'     => 'sku',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Rendererstatus',
        ));
		
		$this->addColumn('qty', array(
			'header'    => Mage::helper('catalog')->__('Qté'),
			'width'     => '100',
			'index'     => 'qty',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Rendererstatus',
		));
		
        $this->addColumn('order_create', array(
            'header'    => Mage::helper('catalog')->__('Date de création'),
			'type'  	=> 'date',
            'index'     => 'order_create'
        ));
		
        $this->addColumn('order_estimate', array(
            'header'    => Mage::helper('catalog')->__('Date estimé de livraison'),
            'index'     => 'order_estimate',
			'type' 	 => 'date',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Rendererstatus',
        ));
		
		$this->addColumn('status', array(
            'header'    => Mage::helper('catalog')->__('Status'),
            'index'     => 'status',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Status',
        ));	
		
		$this->addColumn('print', array(
            'header'    => Mage::helper('catalog')->__('Print'),
            'index'     => 'print',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Printorder',
        ));	
		
		$this->addColumn('edit', array(
            'header'    => Mage::helper('catalog')->__('Edit'),
            'index'     => 'edit',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Editorder',
        ));	

		$this->addColumn('delete', array(
            'header'    => Mage::helper('catalog')->__('Delete'),
            'index'     => 'delete',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Deleteorder',
			'confirm' 	=> 'Are you sure?'
        ));		
		
		//$this->addExportType('*/*/exportPdf', Mage::helper('customer')->__('PDF'));		
		  
        return parent::_prepareColumns();
    }

	
	public function getGridUrl()
    {
        return $this->getUrl('*/*/edit/grid/status', array('_current'=>true));
    }
	
	
    public function getRowUrl($row)
    {
		$data = $row->getData();
       // return $this->getUrl('*/*/delete', array('id'=>$this->getRequest()->getParam('id'),'id_provider_order' => $data['id_provider_order']));
    }	
}
