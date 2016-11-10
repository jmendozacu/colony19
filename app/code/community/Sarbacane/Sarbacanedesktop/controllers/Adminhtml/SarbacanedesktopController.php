<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Sarbacane
 * @package     Sarbacane_Sarbacanedesktop
 * @author      Sarbacane Software <contact@sarbacane.com>
 * @copyright   2015 Sarbacane Software
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class Sarbacane_Sarbacanedesktop_Adminhtml_SarbacanedesktopController extends Mage_Adminhtml_Controller_Action
{

	public function indexAction(){
		if (Mage::app()->getRequest()->getParam('submit_configuration') || Mage::app()->getRequest()->getParam('submit_parameter_key')) {
			if (Mage::app()->getRequest()->getParam('submit_configuration')) {
				$this->saveListConfiguration();
				if (Mage::helper('sarbacanedesktop')->getConfiguration('sd_token') == '') {
					$this->saveTokenParameterConfiguration();
				}
				$this->_getSession()->addSuccess($this->__('Your settings have been saved'));
			}
			else if (Mage::app()->getRequest()->getParam('submit_parameter_key')) {
				$this->saveTokenParameterConfiguration();
				$this->_getSession()->addSuccess($this->__('Your settings have been saved'));
			}
		}
		
		$this->loadLayout();
		$block = $this->getLayout()->getBlock('sarbacanedesktop');
		$block->setSdFormKey($this->getSdFormKey());
		$block->setKeyForSynchronisation($this->getKeyForSynchronisation());
		$block->setListConfiguration(Mage::helper('sarbacanedesktop')->getListConfiguration('array'));
		$block->setGeneralConfiguration(Mage::helper('sarbacanedesktop')->getConfiguration('nb_configured'));
		$block->setStoresArray(Mage::helper('sarbacanedesktop')->getStoresArray());
		if(Mage::app()->getRequest()->getParam("sd_is_user")){
			Mage::getSingleton("core/session")->setData("sd_is_user",Mage::app()->getRequest()->getParam("sd_is_user"));
		}
		$this->_title($this->__('Sarbacane Desktop'));
		$this->_setActiveMenu('newsletter');
		$this->renderLayout();
	}
	
	private function saveListConfiguration()
	{
		$resource = Mage::getSingleton('core/resource');
		$db_write = $resource->getConnection('core_write');
		$sarbacanedesktop_users = $resource->getTableName('sarbacanedesktop_users');
		$shops = '';
		if (Mage::app()->getRequest()->getParam('store_id')) {
			$stores_id = Mage::app()->getRequest()->getParam('store_id');
			if (is_array($stores_id)) {
				foreach ($stores_id as $store_id) {
					$shops .= $store_id . ',';
				}
				$shops = substr($shops, 0, strlen($shops)-1);
			}
		}
		$old_sd_list_array = Mage::helper('sarbacanedesktop')->getListConfiguration('array');
		$rq_sql = '
		UPDATE `' . $sarbacanedesktop_users . '`
		SET `sd_value` = ' . $db_write->quote($shops) . '
		WHERE `sd_type` = \'sd_list\'';
		$db_write->query($rq_sql);
		$sd_list_array = Mage::helper('sarbacanedesktop')->getListConfiguration('array');
		foreach ($sd_list_array as $sd_list)
		{
			if (!in_array($sd_list, $old_sd_list_array))
			{
				Mage::helper('sarbacanedesktop')->resetList($sd_list);
			}
		}
	}
	private function createTokenParameterConfiguration(){
		$resource = Mage::getSingleton('core/resource');
		$db_write = $resource->getConnection('core_write');
		$sarbacanedesktop_users = $resource->getTableName('sarbacanedesktop_users');
		$rq_sql = 'TRUNCATE `' . $sarbacanedesktop_users . '`';
		$db_write->query($rq_sql);
		$token_parameter = rand(100000, 999999) . Mage::getModel('core/date')->timestamp(time());
		$rq_sql = 'INSERT INTO `' . $sarbacanedesktop_users . '` (`sd_value`,`sd_type`) VALUES (' . $db_write->quote($token_parameter) . ',\'sd_token\')';
		$db_write->query($rq_sql);
		return $token_parameter;
	}
	private function saveTokenParameterConfiguration()
	{
		$resource = Mage::getSingleton('core/resource');
		$db_write = $resource->getConnection('core_write');
		$sarbacanedesktop_users = $resource->getTableName('sarbacanedesktop_users');
		$token_parameter = rand(100000, 999999) . Mage::getModel('core/date')->timestamp(time());
		$rq_sql = '
		UPDATE `' . $sarbacanedesktop_users . '`
		SET `sd_value` = ' . $db_write->quote($token_parameter) . '
		WHERE `sd_type` = \'sd_token\'';
		$db_write->query($rq_sql);
		return $token_parameter;
	}

	private function getKeyForSynchronisation()
	{
		return str_rot13('sarbacanedesktop?stk=' . Mage::helper('sarbacanedesktop')->getToken());
	}

	private function getSdFormKey()
	{
		return substr(Mage::helper('core')->encrypt('SarbacaneDesktopForm'), 0, 15);
	}

}