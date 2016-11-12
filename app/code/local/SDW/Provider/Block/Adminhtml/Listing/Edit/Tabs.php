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
		$idProvider = (int)$this->getRequest()->getParam('id');

		if($grid == 'commande' || $grid == 'status'){
			$active = false;
			$content = '';
		}
		else{
			$active = true;
			$content = $this->getLayout()->createBlock('provider/adminhtml_listing_edit_tab_form')->toHtml();
		}
		
		$this->addTab('form_section', array(
											'label' 	=> 'Information Fournisseur',
											'title' 	=> 'Information Fournisseur',
											'content' 	=> $content,
											'active'	=> $active,
											'url'   	=> $this->getUrl('*/*/edit',array('id'=>$idProvider, 'grid'=>'')),
										)
					);
		
		if($idProvider > 0){
			if($grid != 'commande'){
				$active = false;
				$content = '';
			}
			else{
				$active = true;
				$content = $this->getLayout()->createBlock('provider/adminhtml_listing_edit_tab_commande')->toHtml();
			}
			$this->addTab('form_commande', array(
												'label' 	=> 'Création Commande',
												'title' 	=> 'Création Commande',
												'content' 	=> $content,
												'active'	=> $active,
												'url'   	=> $this->getUrl('*/*/edit',array('id'=>$idProvider, 'grid'=>'commande')),
											)
						);		
			
			
			if($grid != "status"){
				$active = false;
				$content = '';
			}
			else{
				$active = true;
				$content = $this->getLayout()->createBlock('provider/adminhtml_listing_edit_tab_status')->toHtml();
			}
			$this->addTab('form_status', array(
												'label'		=> 'Status Commande',
												'title'		=> 'Status Commande',
												'content' 	=> $content,
												'active'	=> $active,
												'url'   	=> $this->getUrl('*/*/edit',array('id'=>$idProvider, 'grid'=>'status')),
											)
						);		
		}
		
		return parent::_beforeToHtml();
	}
}
