<?php
class SDW_Provider_Block_Adminhtml_Listing_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
	public function __construct()
	{
		parent::__construct();
		$this->setId('provider_tabs');
		$this->setDestElementId('edit_form');
		$this->setTitle('Information sur le Fournisseur');
	}
	protected function _beforeToHtml()
	{
		$grid = $this->getRequest()->getParam('grid');

		$active = true;
		if($grid == 'commande' || $grid == 'status')$active = false;	
		$this->addTab('form_section', array(
											'label' => 'Information Fournisseur',
											'title' => 'Information Fournisseur',
											'content' => $this->getLayout()
															->createBlock('provider/adminhtml_listing_edit_tab_form')
															->toHtml(),
											'active'=>$active
										)
					);
		
		$active = true;
		if($grid != 'commande')$active = false;
		$this->addTab('form_commande', array(
											'label' => 'Création Commande',
											'title' => 'Création Commande',
											'content' => $this->getLayout()
															->createBlock('provider/adminhtml_listing_edit_tab_commande')
															->toHtml(),
											'active'=>$active
										)
					);		
		
		$active = true;
		if($grid != "status")$active = false;
		$this->addTab('form_status', array(
											'label' => 'Status Commande',
											'title' => 'Status Commande',
											'content' => $this->getLayout()
															->createBlock('provider/adminhtml_listing_edit_tab_status')
															->toHtml(),
											'active'=>$active
										)
					);
					
		return parent::_beforeToHtml();
	}
}
