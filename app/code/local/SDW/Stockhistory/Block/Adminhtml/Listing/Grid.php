<?php

class SDW_Stockhistory_Block_Adminhtml_Listing_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
   public function __construct()
   {
       parent::__construct();
       $this->setId('stockHistoryGrid');
       $this->setDefaultSort('id_log');
       $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);
   }
   protected function _prepareCollection()
   {
      $collection = Mage::getModel('stockhistory/stockhistory')->getCollection();

      $this->setCollection($collection);
      return parent::_prepareCollection();
    }
	
   protected function _prepareColumns()
   {
		$this->addColumn('id_log',
             array(
                    'header' => $this->__('ID'),
					'type'  => 'number',
                    'align' =>'right',
					'width' => '50px',
					'type'  => 'number',
                    'index' => 'id_log',
               ));
			   
		$this->addColumn('file_local',
             array(
                    'header' => $this->__('Fichier Local'),
                    'align' => 'left',
					'width' => '200px',
                    'index' => 'file_local',
               ));		
			   
		$this->addColumn('file_ads',
             array(
                    'header' => $this->__('Fichier ADS'),
                    'align' => 'left',
					'width' => '200px',
                    'index' => 'file_ads',
               ));	
			   
		$this->addColumn('date',
             array(
                    'header' => $this->__('Date'),
                    'align' => 'left',
					'width' => '100px',
                    'index' => 'date',
					'type'  => 'datetime',
					'renderer'  => 'SDW_Stockhistory_Block_Adminhtml_Listing_Render_Renderer',
               ));	
			   
		$this->addColumn('stock',
             array(
                    'header' => $this->__('Mouvements de stock'),
                    'align' => 'left',
                    'index' => 'stock',
					'filter'    => false,
					'sortable'  => false,
					'renderer'  => 'SDW_Stockhistory_Block_Adminhtml_Listing_Render_Renderer',
               ));	
			   
		$this->addColumn('status',
             array(
                    'header' => $this->__('Status'),
                    'align' => 'center',
					'width' => '50px',
                    'index' => 'status', 
					'type'  => 'options',
					'options'    => array('0' => 'KO','1' => 'OK')
               ));	

		return parent::_prepareColumns();
    }

	
    public function getRowUrl($row)
    {
        return ;
    }
}
