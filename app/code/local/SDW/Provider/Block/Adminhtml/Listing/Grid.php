<?php

class SDW_Provider_Block_Adminhtml_Listing_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
   public function __construct()
   {
       parent::__construct();
       $this->setId('providerGrid');
       $this->setDefaultSort('id_provider');
       $this->setDefaultDir('DESC');
       $this->setSaveParametersInSession(true);
   }
   protected function _prepareCollection()
   {
      $collection = Mage::getModel('provider/provider')->getCollection();

      $this->setCollection($collection);
      return parent::_prepareCollection();
    }
	
   protected function _prepareColumns()
   {
		$this->addColumn('id_provider',
             array(
                    'header' => 'ID',
					'type'  => 'number',
                    'align' =>'right',
					'width' => '50px',
					'type'  => 'number',
                    'index' => 'id_provider',
               ));
		$this->addColumn('code',
               array(
                    'header' => 'Code Fournisseur',
                    'align' =>'left',
                    'index' => 'code',
              ));
		$this->addColumn('sociale', array(
                    'header' => 'Raison Sociale',
                    'align' =>'left',
                    'index' => 'sociale',
             ));
		 
		$this->addColumn('action',
            array(
                'header'    =>  'Action',
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => 'Edit',
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		  
        return parent::_prepareColumns();
    }
	
	protected function _prepareMassaction()
    {
		$this->setMassactionIdField('id_provider');
		$this->getMassactionBlock()->setFormFieldName('customer');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'=> 'Delete',
             'url'  => $this->getUrl('*/*/massDelete'),
             'confirm' => 'Are you sure?'
        ));		
        return $this;
    }

	
    public function getRowUrl($row)
    {
         return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}
