<?php
class SDW_Weighthistory_Adminhtml_ListingController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Weight History'), Mage::helper('adminhtml')->__('Weight History'));

		return $this;
	}  
	
	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}
}

