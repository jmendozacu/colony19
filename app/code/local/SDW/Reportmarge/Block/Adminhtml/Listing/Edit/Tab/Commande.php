<?php
class SDW_Provider_Block_Adminhtml_Listing_Edit_Tab_Commande extends Mage_Adminhtml_Block_Widget_Grid
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('providerCommande');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('DESC');
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
			->addAttributeToSelect('provider')
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
			

		//echo $collection->getSelect();die();
	

		$collection->getSelect()->where('e.type_id = ?', "simple");
		
		parent::_prepareCollection();
		
		$collection->setPageSize(5000);
					
        $this->setCollection($collection);

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
			'sortable'  => false,
			'width'     => 60,
            'index'     => 'name'
        ));

        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => 80,
			'sortable'  => false,
            'index'     => 'sku'
        ));
		
		$this->addColumn('ref_fournisseur', array(
            'header'    => Mage::helper('catalog')->__('Réf. Fournisseur'),
            'width'     => 80,
			'sortable'  => false,
            'index'     => 'ref_fournisseur'
        ));
		
		$this->addColumn('semaine', array(
			'header'    => Mage::helper('catalog')->__('Semaine'),
			'width'     => '100',
			'index'     => 'semaine',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Semaine',
		));
		
		$this->addColumn('annee', array(
			'header'    => Mage::helper('catalog')->__('Année'),
			'width'     => '100',
			'index'     => 'annee',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Annee',
		));
		
		$this->addColumn('qty', array(
			'header'    => Mage::helper('catalog')->__('Qté'),
			'width'     => '100',
			'type'  	=> 'number',
			'index'     => 'qty',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Qty',
		));
		
		$this->addColumn('alerte', array(
			'header'    => Mage::helper('catalog')->__('Alerte'),
			'width'     => '100',
			'index'     => 'alerte',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Alerte',
		));
		
		$this->addColumn('requis', array(
			'header'    => Mage::helper('catalog')->__('Nécessaire'),
			'width'     => '100',
			'type'  	=> 'number',
			'index'     => 'requis',
			'filter'    => false,
			'sortable'  => false,
			'renderer'  => 'SDW_Provider_Block_Adminhtml_Listing_Render_Requis',
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
