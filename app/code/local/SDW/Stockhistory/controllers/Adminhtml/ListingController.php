<?php
class SDW_Stockhistory_Adminhtml_ListingController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Stock History'), Mage::helper('adminhtml')->__('Stock History'));

		return $this;
	}  
	
	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}
}

