<?php

require_once Mage::getModuleDir('controllers', 'Mage_Catalog').'/CategoryController.php';

class SDW_Catalog_CategoryController extends Mage_Catalog_CategoryController
{
	protected function _initCatagory()
	{
		$category = parent::_initCatagory();

		if (
			Mage::app()->getWebsite()->getCode() === 'powell'
			&&
			!Mage::getSingleton('customer/session')->isLoggedIn()
			&&
			$category->getLevel() > 2
		) {
			Mage::getSingleton('core/session')->addError($this->__('Accès réservé aux professionnels. Nous vous remercions de saisir vos identifiants.'));
			$this->_redirect('customer/account/login');
			return false;
		}

		return $category;
	}
}
