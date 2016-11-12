<?php
class SDW_Provider_Block_Adminhtml_Listing_Edit_Tab_Commande extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('providerCommande');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(false);
		$this->setUseAjax(false);
		$this->setTemplate('provider/widget/grid.phtml');		
	}
	
	protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

	protected function _prepareCollection()
	{
		$store = $this->_getStore();
		
		$collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('provider')
			->addAttributeToSelect('ref_fournisseur')
			->addFieldToFilter('provider', $this->getRequest()->getParam('id'))
			->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
				

		$collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
		
		$collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );       
            
        $collection->joinAttribute(
                'suivi',
                'catalog_product/suivi',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
			
			
		$collection->getSelect()->where('e.type_id = ?', "simple");

		$this->setCollection($collection);		
		
		parent::_prepareCollection();

        return $this;
    }

	protected function _prepareColumns()
	{

        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => true,
            'width'     => 60,
            'index'     => 'entity_id'
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
			'width'     => 60,
            'index'     => 'name'
        ));

        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => 80,
            'index'     => 'sku'
        ));
		
		$this->addColumn('ref_fournisseur', array(
            'header'    => Mage::helper('catalog')->__('Réf. Fournisseur'),
            'width'     => 80,
            'index'     => 'ref_fournisseur'
        ));
		
		$this->addColumn('status',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'width' => '70px',
                'index' => 'status',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));
        $this->addColumn('suivi',
            array(
                'header'=> Mage::helper('catalog')->__('Suivi'),
                'width' => '70px',
                'index' => 'suivi',
                'type'  => 'options',
                'options' => array(0=>'Non',1=>'Oui'),
        ));
		
		$this->addColumn('semaine', array(
			'header'    => Mage::helper('catalog')->__('Semaine'),
			'width'     => '100',
			'index'     => 'semaine',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Renderer',
		));
		
		$this->addColumn('annee', array(
			'header'    => Mage::helper('catalog')->__('Année'),
			'width'     => '100',
			'index'     => 'annee',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Renderer',
		));
		
		$this->addColumn('qty', array(
			'header'    => Mage::helper('catalog')->__('Qté'),
			'width'     => '100',
			'type'  	=> 'number',
			'index'     => 'qty',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Renderer',
		));
		
		$this->addColumn('alerte', array(
			'header'    => Mage::helper('catalog')->__('Alerte'),
			'width'     => '100',
			'index'     => 'alerte',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Renderer',
		));
		
		$this->addColumn('requis', array(
			'header'    => Mage::helper('catalog')->__('Nécessaire'),
			'width'     => '100',
			'type'  	=> 'number',
			'index'     => 'requis',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Renderer',
		));
		
		$this->addColumn('commander', array(
			'header'    => Mage::helper('catalog')->__('Commander'),
			'width'     => '100',
			'index'     => 'commander',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Commander',
		));

        return parent::_prepareColumns();
    }
	
	public function getGridUrl()
    {
        return $this->getUrl('*/*/edit/grid/commande', array('_current'=>true));
    }
}
