<?php

class SDW_Weighthistory_Block_Adminhtml_Listing_Grid extends Mage_Adminhtml_Block_Report_Grid
{
  /**
     * Sub report size
     *
     * @var int
     */
    protected $_subReportSize = 0;
	
	

    /**
     * Initialize Grid settings
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('gridWeighthistory');
    }
	

    /**
     * Prepare collection object for grid
     *
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->getCollection()->initReport('reports/product_weight_collection');

        return $this;
    }

    /**
     * Prepare Grid columns
     *
     */
    protected function _prepareColumns()
    {

		$this->addColumn('row_name',array(
			'header'		=>  $this->__('Informations'),
			'index' 		=> 'row_name',
			'filter'    	=> false,
            'sortable'  	=> false,
			'align'     	=> 'left',
        ));
		
		$this->addColumn('ordered_total',array(
			'header'		=> Mage::helper('catalog')->__('CA HT (€)'),
			'type'  		=> 'price',
			'width'     	=> 80,
			'index' 		=> 'ordered_total',
			'filter'    	=> false,
            'sortable'  	=> false,
			'align'     	=> 'right',
        ));

		$this->addColumn('marge_brute',array(
			'header'		=> Mage::helper('catalog')->__('MB (€)'),
			'type'  		=> 'price',
			'width'     	=> 80,
			'index' 		=> 'marge_brute',			'filter'    	=> false,
            'sortable'  	=> false,
			'align'     	=> 'right',
        ));
		
		$this->addColumn('taux_marge_brute',array(
			'header'		=> Mage::helper('catalog')->__('%MB/CA'),
			'width'     	=> 80,
			'type'  		=> 'number',
			'index' 		=> 'taux_marge_brute', 
			'filter'    	=> false,
            'sortable'  	=> false,
			'align'    		=> 'right',
        ));	
		
		$this->addColumn('taux_marge_brute_total',array(
			'header'		=> Mage::helper('catalog')->__('%MB/Total'),
			'width'     	=> 80,
			'type'  		=> 'number',
			'index' 		=> 'taux_marge_brute_total',
			'filter'    	=> false,
            'sortable'  	=> false,
			'align'    	 	=> 'right',
        ));	

        return parent::_prepareColumns();
    }
}
