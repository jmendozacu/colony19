<?php
class SDW_Provider_Adminhtml_ListingController extends Mage_Adminhtml_Controller_Action
{
	protected function _initAction() {
		$this->loadLayout()->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

		return $this;
	}  
	
	public function indexAction()
	{
		$this->loadLayout()->renderLayout();
	}
	
	public function editAction()
	{
		
		$providerId = $this->getRequest()->getParam('id');
		$providerModel = Mage::getModel('provider/provider')->load($providerId);
		
		if ($providerModel->getId() || $providerId == 0)
		{
			Mage::register('provider_data', $providerModel);
			
			$this->loadLayout();
			$this->_setActiveMenu('provider/set_time');
			$this->_addBreadcrumb('provider Manager', 'provider Manager');
			$this->_addBreadcrumb('provider Description', 'provider Description');
			
			$this->_addContent($this->getLayout()->createBlock('provider/adminhtml_provider_edit'))
					->_addLeft($this->getLayout()->createBlock('provider/adminhtml_provider_edit_tabs'));
					
			$this->renderLayout();
		}
		else
		{
			Mage::getSingleton('adminhtml/session')->addError('provider does not exist');
			$this->_redirect('*/*/');
		}
	}
	
	public function editorderAction()
	{
		$providerId = (int) $this->getRequest()->getParam('id');
		$id_order = (int) $this->getRequest()->getParam('id_order');
		$grid = $this->getRequest()->getParam('grid');
		
		if ($providerId && $id_order) {
			// fetch write database connection that is used in Mage_Core module
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$this->_redirect('*/*/edit', array('id' => $providerId,'provider_order_id' => $id_order,'grid'=>'commande'));
		}
		else $this->_redirect('*/*/edit', array('id' => $providerId,'grid'=>'status'));
	}
	
	public function newAction()
	{
		$this->_forward('edit');
	}
	
	public function exportPdfAction(){
		$providerId = (int) $this->getRequest()->getParam('id');
		
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$providerOrder = $read->fetchAll("SELECT * FROM `mgt_sdw_provider_order` 
													LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
													WHERE `mgt_sdw_provider_order`.status = 'Livraison' 
													AND `mgt_sdw_provider_order`.id_provider=".$providerId);

		$pdf = Mage::getModel('provider/pdf_provider')->getPdf($providerOrder);

		return $this->_prepareDownloadResponse(
                'provider'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(),
                'application/pdf'
            );
	}
	
	public function saveAction()
	{
		if ($this->getRequest()->getPost())
		{
			try {
				$postData = $this->getRequest()->getPost();
				$providerModel = Mage::getModel('provider/provider');
				if( $this->getRequest()->getParam('id') <= 0 )
					$providerModel->setCreatedTime(Mage::getSingleton('core/date')->gmtDate());
					
				$providerModel->addData($postData)->setUpdateTime(
				Mage::getSingleton('core/date')->gmtDate())
												->setId($this->getRequest()->getParam('id'))
												->save();
				Mage::getSingleton('adminhtml/session')->addSuccess('successfully saved');
				Mage::getSingleton('adminhtml/session')->setproviderData(false);
				$this->_redirect('*/*/');
				return;
			} catch (Exception $e){
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				Mage::getSingleton('adminhtml/session')->setproviderData($this->getRequest()->getPost());
				$this->_redirect('*/*/edit',array('id' => $this->getRequest()->getParam('id')));
				return;
			}
		}
		$this->_redirect('*/*/');
	}
	
	public function deleteAction()
	{
		if($this->getRequest()->getParam('id') > 0)
		{
			try
			{
				$providerModel = Mage::getModel('provider/provider');
				$providerModel->setId($this->getRequest()->getParam('id'))->delete();
				Mage::getSingleton('adminhtml/session')->addSuccess('successfully deleted');
				$this->_redirect('*/*/');
			}
			catch (Exception $e)
			{
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}
	
	public function deleteorderAction()
	{
		$providerId = (int) $this->getRequest()->getParam('id');
		$providerOrderId = (int) $this->getRequest()->getParam('id_order');
		
		
		if ($providerId!='' && $providerOrderId!='') {
			// fetch write database connection that is used in Mage_Core module
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');

			// now $write is an instance of Zend_Db_Adapter_Abstract
			$write->query("DELETE FROM `mgt_sdw_provider_order` WHERE id_provider=".$providerId." AND provider_order_id=".$providerOrderId);			
		}
		$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id'),'grid'=>'status'));
	}
	
	public function commanderAction()
	{
		$providerId = (int) $this->getRequest()->getPost('provider_id');
		$provider_order_id = (int) $this->getRequest()->getPost('provider_order_id');
		
		if ($providerId) {
			// fetch write database connection that is used in Mage_Core module
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');			
			
			if($provider_order_id > 0){
				$data = $this->getRequest()->getPost('product_quantity');				
				$errors = array();
				if (count($data)>0) {
					foreach ($data as $productId => $qty) {
						$commande = $read->fetchRow("SELECT COUNT(id_provider_order) as present
													FROM `mgt_sdw_provider_order` 
													WHERE id_provider= ".$providerId." 
													AND provider_order_id = ".$provider_order_id." 
													AND product_id = ".(int)$productId);
		
						// now $write is an instance of Zend_Db_Adapter_Abstract
						if($commande['present'] > 0 && (int)$qty > 0){
							$write->query("UPDATE `mgt_sdw_provider_order` 
										SET `quantity` = ".(int)$qty."
										WHERE `provider_order_id` = ".(int)$provider_order_id."
										AND `id_provider` = ".(int)$providerId."
										AND `product_id` = ".(int)$productId);
						}
						else if((int)$qty > 0){	
							$write->query("INSERT INTO `mgt_sdw_provider_order` (`id_provider`, `product_id`, `quantity`, `order_create`, `status`, `provider_order_id`) 
									VALUES ('".(int)$providerId."', '".(int)$productId."', '".(int)$qty."', NOW(), 'En attente', '".(int)$provider_order_id."')");
						}
						else {
							$write->query("DELETE FROM `mgt_sdw_provider_order` 
										WHERE `provider_order_id` = ".(int)$provider_order_id."
										AND `id_provider` = ".(int)$providerId."
										AND `product_id` = ".(int)$productId);
						}
					}
				}
			}
			else{			
				$read = Mage::getSingleton('core/resource')->getConnection('core_read');
				$lastOrder = $read->fetchRow("SELECT provider_order_id FROM `mgt_sdw_provider_order` ORDER BY provider_order_id DESC LIMIT 1");
				$order_id = (int)$lastOrder['provider_order_id'] + 1;
				
				$data = $this->getRequest()->getPost('product_quantity');
				$errors = array();
				if (count($data)>0) {
					foreach ($data as $productId => $qty) {
						// now $write is an instance of Zend_Db_Adapter_Abstract
						if((int)$qty > 0){						
							$write->query("INSERT INTO `mgt_sdw_provider_order` (`id_provider`, `product_id`, `quantity`, `order_create`, `status`, `provider_order_id`) 
									VALUES ('".(int)$providerId."', '".(int)$productId."', '".(int)$qty."', NOW(), 'En attente', '".$order_id."')");
						}
					}
				}
			}
		}
		
		$this->_redirect('*/*/edit', array('id' => $providerId,'grid'=>'status'));
	}
	
	
	public function printorderAction()
	{
		$providerId = (int) $this->getRequest()->getParam('id');
		$order_id = (int) $this->getRequest()->getParam('id_order');
		
		
		if ($providerId!='' && $order_id!='') {
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');

			$providerOrder = $read->fetchAll("SELECT * FROM `mgt_sdw_provider_order` 
														LEFT JOIN `mgt_sdw_provider` ON `mgt_sdw_provider`.id_provider = `mgt_sdw_provider_order`.id_provider
														WHERE `mgt_sdw_provider_order`.provider_order_id = ".$order_id." 
														AND `mgt_sdw_provider_order`.id_provider=".$providerId);
														
														
			$provider = $read->fetchRow("SELECT * FROM `mgt_sdw_provider` WHERE `mgt_sdw_provider`.id_provider=".$providerId);									

			$pdf = Mage::getModel('provider/pdf_provider')->getPdf($providerOrder,$provider);	
			
			$name = 'commande-'.Mage::getSingleton('core/date')->date('Y-m-d').'.pdf';
			
			if(!empty($provider) && $provider['code'] !='' && $providerOrder[0]['order_create'] !=''){	
				$name = 'commande-'.$provider['code'].'-'.Mage::getSingleton('core/date')->date('Y-m-d',$providerOrder[0]['order_create']).'.pdf';
			}

			return $this->_prepareDownloadResponse(
                $name, $pdf->render(),
                'application/pdf'
            );		
		}
		$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id'),'grid'=>'status'));
	}
	
	public function statusAction()
	{
		$idProvider = (int) $this->getRequest()->getPost('idProvider');
		$providerOrderId = (int) $this->getRequest()->getPost('providerOrderId');
		$value = $this->getRequest()->getPost('value');
		
		// fetch write database connection that is used in Mage_Core module
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		// now $write is an instance of Zend_Db_Adapter_Abstract
		$write->query("UPDATE `mgt_sdw_provider_order` SET status = '".$value."' WHERE id_provider=".$idProvider." AND provider_order_id=".$providerOrderId);
	}
}

