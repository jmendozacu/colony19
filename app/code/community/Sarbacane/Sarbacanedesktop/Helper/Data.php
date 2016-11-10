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

class Sarbacane_Sarbacanedesktop_Helper_Data extends Mage_Core_Helper_Abstract
{

	public function getStoresArray()
	{
		$stores = Mage::app()->getStores();
		$stores_array = array();
		foreach ($stores as $store) {
			$stores_array[] = array('store_id' => $store->store_id, 'store_name' => $store->name);
		}
		return $stores_array;
	}

	public function getStoreidFromList($list)
	{
		if (substr($list, -1) == 'N' || substr($list, -1) == 'C') {
			return substr($list, 0, -1);
		} else {
			return substr($list, 0, -2);
		}
	}

	public function getListTypeFromList($list)
	{
		if (substr($list, -1) == 'N' || substr($list, -1) == 'C') {
			return substr($list, -1);
		} else {
			return substr($list, -2, 1);
		}
	}

	public function getListTypeArray()
	{
		return array('N', 'C');
	}

	public function getListConfiguration($return = 'string')
	{
		$sd_list = $this->getConfiguration('sd_list');
		if ($return == 'string') {
			return $sd_list;
		} else {
			if (strlen($sd_list) != 0) {
				return explode(',', $sd_list);
			}
			return array();
		}
	}

	public function resetList($list_id = '')
	{
		$id_shop = $this->getStoreidFromList($list_id);
		$list_type = $this->getListTypeFromList($list_id);
		
		$resource = Mage::getSingleton('core/resource');
		$db_write = $resource->getConnection('core_write');
		$sarbacanedesktop = $resource->getTableName('sarbacanedesktop_users');
		$rq_sql = '
		DELETE FROM `' . $sarbacanedesktop . '` 
		WHERE `sd_type` = "sd_id"
		AND `list_id` = ' . $db_write->quote($id_shop.$list_type);
		$db_write->query($rq_sql);
	}

	public function getToken()
	{
		$str = $this->getConfiguration('sd_token');
		$str = $str . substr(Mage::helper('core')->encrypt('SecurityTokenForModule'), 0, 11) . $str;
		$str = md5($str);
		return $str;
	}

	public function getConfiguration($return = 'nb_configured')
	{
		$resource = Mage::getSingleton('core/resource');
		$db_read = $resource->getConnection('core_read');
		$sarbacanedesktop_users = $resource->getTableName('sarbacanedesktop_users');
		$rq_sql = '
		SELECT *
		FROM `' . $sarbacanedesktop_users . '`
		WHERE `sd_type` = \'sd_token\'
		OR `sd_type` = \'sd_list\'';
		$rq = $db_read->query($rq_sql);
		$sd_token = '';
		$sd_list = '';
		while ($r = $rq->fetch()) {
			if ($r['sd_type'] == 'sd_token') {
				$sd_token = $r['sd_value'];
			}
			else if ($r['sd_type'] == 'sd_list') {
				$sd_list = $r['sd_value'];
			}
		}
		if ($return == 'sd_token' || $return == 'sd_list') {
			if ($return == 'sd_token') {
				return $sd_token;
			}
			else if ($return == 'sd_list') {
				return $sd_list;
			}
		} else {
			if ($return == 'all') {
				return array(
					'sd_token' => $sd_token,
					'sd_list' => $sd_list
				);
			} else {
				$nb_configured = 0;
				if ($sd_token != '') {
					$nb_configured++;
				}
				if ($sd_list != '') {
					$nb_configured++;
				}
				return $nb_configured;
			}
		}
	}
	
	public function deleteSdid($sd_id){
		$resource = Mage::getSingleton('core/resource');
		$db_write = $resource->getConnection('core_write');
		$sarbacanedesktop_users = $resource->getTableName('sarbacanedesktop_users');
		
		$rq_sql = 'DELETE FROM '.$sarbacanedesktop_users.' WHERE sd_value = "'.$sd_id.'" AND sd_type="sd_id"';
		$db_write->query($rq_sql);
	}

}