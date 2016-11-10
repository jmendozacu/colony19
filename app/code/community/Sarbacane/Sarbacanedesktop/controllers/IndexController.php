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
class Sarbacane_Sarbacanedesktop_IndexController extends Mage_Core_Controller_Front_Action {
	private function saveSdid($sdid, $list) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_write = $resource->getConnection ( 'core_write' );
		$sarbacanedesktop_users = $resource->getTableName ( 'sarbacanedesktop_users' );
		
		$rq_sql = 'DELETE FROM `' . $sarbacanedesktop_users . '` WHERE sd_type=\'sd_id\' AND sd_value=\'' . $sdid . '\' AND list_id=\'' . $list . '\'';
		$rq = $db_write->query ( $rq_sql );
		$rq_sql = '
		INSERT INTO `' . $sarbacanedesktop_users . '` (`sd_type`, `sd_value`, `list_id`, `last_call_date` ) VALUES
		(\'sd_id\', ' . $db_write->quote ( $sdid ) . ', \'' . $list . '\', \'' . date ( 'Y-m-d H:i:s' ) . '\')';
		$rq = $db_write->query ( $rq_sql );
		return;
	}
	public function indexAction() {
		if (Mage::app ()->getRequest ()->getParam ( 'stk' ) && Mage::app ()->getRequest ()->getParam ( 'sdid' )) {
			$sdid = Mage::app ()->getRequest ()->getParam ( 'sdid' );
			if (Mage::app ()->getRequest ()->getParam ( 'stk' ) == Mage::helper ( 'sarbacanedesktop' )->getToken () && $sdid != '') {
				$identifier = Mage::helper ( 'sarbacanedesktop' )->getConfiguration ( 'identifier' );
				$sd_id = "";
				$configuration = Mage::helper ( 'sarbacanedesktop' )->getConfiguration ( 'all' );
				if ($configuration ['sd_token'] != '' && $configuration ['sd_list'] != '') {
					$sd_list_array = Mage::helper ( 'sarbacanedesktop' )->getListConfiguration ( 'array' );
					if ($sd_list_array != '') {
						if (Mage::app ()->getRequest ()->getParam ( 'list' )) {
							$list = Mage::app ()->getRequest ()->getParam ( 'list' );
							$store_id = Mage::helper ( 'sarbacanedesktop' )->getStoreidFromList ( $list );
							$list_type = Mage::helper ( 'sarbacanedesktop' )->getListTypeFromList ( $list );
							$list_type_array = Mage::helper ( 'sarbacanedesktop' )->getListTypeArray ();
							if (in_array ( $list_type, $list_type_array )) {
								$id_and_list = $store_id . $list_type;
								if (($list_type == 'N' && in_array ( $id_and_list . '0', $sd_list_array )) || ($list_type == 'C' && (in_array ( $id_and_list . '0', $sd_list_array ) || in_array ( $id_and_list . '1', $sd_list_array )))) {
									$this->processNewUnsubcribersAndSubscribers ( $list_type, $store_id, $sdid );
									$sd_id = $this->saveSdid ( $sdid, $list );
								} else {
									header ( 'HTTP/1.1 404 Not found' );
									header ( "Content-type: application/json ; charset=utf-8" );
									die ( 'FAILED_ID' );
								}
							} else {
								header ( 'HTTP/1.1 404 Not found' );
								header ( "Content-type: application/json ; charset=utf-8" );
								die ( 'FAILED_ID' );
							}
						} else {
							if ('reset' == Mage::app ()->getRequest ()->getParam ( 'action' )) {
								Mage::helper ( 'sarbacanedesktop' )->deleteSdid ( $sd_id );
							} else {
								$this->getFormattedContentShops ( $sdid );
							}
						}
					}
				}
			} else {
				header ( "HTTP/1.1 403 Unauthorized" );
				header ( "Content-type: application/json; charset=utf-8" );
				die ( 'FAILED_SDTOKEN' );
			}
		} else {
			echo "Param&egrave;tre[s] manquant[s]";
		}
	}
	private function processNewUnsubcribersAndSubscribers($list_type, $store_id, $sd_id) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_read = $resource->getConnection ( 'core_read' );
		$sarbacanedesktop_users = $resource->getTableName ( 'sarbacanedesktop_users' );
		$rq_sql = 'SELECT last_call_date FROM ' . $sarbacanedesktop_users . ' WHERE sd_type=\'sd_id\' AND sd_value=\'' . $sd_id . '\' AND list_id=\'' . $store_id . $list_type . '\'';
		$last_call_date = $db_read->fetchOne ( $rq_sql );
		
		$line = 'email;lastname;firstname';
		if ($list_type == 'C') {
			if ($this->checkIfListWithCustomerData ( $list_type, $store_id )) {
				$line .= ';date_first_order;date_last_order;amount_min_order;amount_max_order;amount_avg_order;nb_orders;amount_all_orders;most_profitable_category';
			}
		}
		$line .= ';action'."\r\n";
		echo $line;
		$this->processNewUnsubscribers ( $list_type, $store_id, $sd_id, 'display', $last_call_date );
		$this->processNewSubscribers ( $list_type, $store_id, $sd_id, 'display', $last_call_date );
	}
	private function getListTypeArray() {
		return array (
				'N',
				'C' 
		);
	}
	private function checkIfListWithCustomerData($list_type, $store_id) {
		$sd_list_array = Mage::helper ( 'sarbacanedesktop' )->getListConfiguration ( 'array' );
		if (in_array ( $store_id . $list_type . '1', $sd_list_array )) {
			return true;
		}
		return false;
	}
	private function getFormattedContentShops($sd_id) {
		$stores = Mage::helper ( 'sarbacanedesktop' )->getStoresArray ();
		echo 'list_id;name;reset;is_updated;type;version' . "\n";
		$sd_list_array = Mage::helper ( 'sarbacanedesktop' )->getListConfiguration ( 'array' );
		$list_array = array ();
		foreach ( $sd_list_array as $list ) {
			$store_id = Mage::helper ( 'sarbacanedesktop' )->getStoreidFromList ( $list );
			$list_type = Mage::helper ( 'sarbacanedesktop' )->getListTypeFromList ( $list );
			array_push ( $list_array, array (
					'store_id' => $store_id,
					'list_type' => $list_type,
					'list_id' => $list 
			) );
		}
		foreach ( $stores as $store ) {
			foreach ( $list_array as $list ) {
				if ($store ['store_id'] == $list ['store_id']) {
					$store_list = "" . $store ['store_id'] . $list ['list_type'] . ';' . $this->dQuote ( $store ['store_name'] ) . ';'; // TEST
					$store_list .= $this->listIsResetted ( $store ['store_id'] . $list ['list_type'], $sd_id ) . ';';
					$store_list .= $this->listIsUpdated ( $store ['store_id'], $list ['list_type'], $sd_id ) . ';';
					$store_list .= 'Magento;1.0.0.7' . "\r\n";
					echo $store_list;
				}
			}
		}
	}
	private function listIsResetted($list_id, $sd_id) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_read = $resource->getConnection ( 'core_read' );
		$sarbacanedesktop_users = $resource->getTableName ( 'sarbacanedesktop_users' );
		$rq_sql = '
		SELECT count(*) AS `nb_in_table`
		FROM ' . $sarbacanedesktop_users . '
		WHERE `sd_type` = "sd_id"
		AND `sd_value` = ' . $db_read->quote ( $sd_id ) . ' AND list_id="' . $list_id . '"';
		$nb_in_table = $db_read->fetchOne ( $rq_sql );
		if ($nb_in_table == 0)
			return 'Y';
		return 'N';
	}
	private function listIsUpdated($store_id, $list_type, $sd_id) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_read = $resource->getConnection ( 'core_read' );
		$sarbacanedesktop_users = $resource->getTableName ( 'sarbacanedesktop_users' );
		$rq_sql = 'SELECT last_call_date FROM ' . $sarbacanedesktop_users . ' WHERE sd_type=\'sd_id\' AND sd_value=\'' . $sd_id . '\' AND list_id=\'' . $store_id . $list_type . '\'';
		$last_call_date = $db_read->fetchOne ( $rq_sql );
		
		$is_updated_list = 'N';
		if ($this->processNewUnsubscribers ( $list_type, $store_id, $sd_id, 'is_updated', $last_call_date ) > 0) {
			$is_updated_list = 'Y';
		}
		if ($this->processNewSubscribers ( $list_type, $store_id, $sd_id, 'is_updated', $last_call_date ) > 0) {
			$is_updated_list = 'Y';
		}
		return $is_updated_list;
	}
	private function dQuote($value) {
		$value = str_replace ( '"', '""', $value );
		if (strpos ( $value, ' ' ) || strpos ( $value, ';' )) {
			$value = '"' . $value . '"';
		}
		return $value;
	}
	private function processNewSubscribers($list_type, $store_id, $sd_id, $type_action = 'display', $last_call_date) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_read = $resource->getConnection ( 'core_read' );
		$db_write = $resource->getConnection ( 'core_write' );
		$sd_updates = $resource->getTableName ( 'sd_updates' );
		$newsletter_subscriber = $resource->getTableName ( 'newsletter_subscriber' );
		$sales_flat_order = $resource->getTableName ( 'sales_flat_order' );
		$sales_flat_order_item = $resource->getTableName ( 'sales_flat_order_item' );
		$catalog_category_product = $resource->getTableName ( 'catalog_category_product' );
		$catalog_category_entity_varchar = $resource->getTableName ( 'catalog_category_entity_varchar' );
		$customer_entity = $resource->getTableName ( 'customer_entity' );
		$customer_entity_varchar = $resource->getTableName ( 'customer_entity_varchar' );
		$core_store = $resource->getTableName ( 'core_store' ); // core store
		$attr_firstname = Mage::getModel ( 'customer/customer' )->getAttribute ( 'firstname' )->getAttributeId ();
		$attr_lastname = Mage::getModel ( 'customer/customer' )->getAttribute ( 'lastname' )->getAttributeId ();
		if ($list_type == 'N') {
			if ($last_call_date != null && $last_call_date != '') {
				$rq_sql = 'SELECT sdu.`email` AS `email`, IFNULL(lastname.`value`, \'\') AS `lastname`, IFNULL(firstname.`value`, \'\') AS `firstname`
				FROM `' . $sd_updates . '` AS `sdu`
				LEFT JOIN ' . $newsletter_subscriber . ' ns ON ns.subscriber_email = sdu.email
				LEFT JOIN ' . $customer_entity_varchar . ' AS `lastname` ON lastname.`entity_id` = ns.`customer_id` AND lastname.`attribute_id` = ' . ( int ) $attr_lastname . '
				LEFT JOIN ' . $customer_entity_varchar . ' AS `firstname` ON firstname.`entity_id` = ns.`customer_id` AND firstname.`attribute_id` = ' . ( int ) $attr_firstname . '
				WHERE ns.`subscriber_status` = 1 AND sdu.update_time > "' . $last_call_date . '" AND sdu.list_type="N" AND sdu.action="S"
				AND ns.`store_id` = ' . ( int ) $store_id;
			} else {
				$rq_sql = '
				SELECT ns.`subscriber_email` AS `email`, IFNULL(lastname.`value`, \'\') AS `lastname`, IFNULL(firstname.`value`, \'\') AS `firstname`
				FROM `' . $newsletter_subscriber . '` AS `ns`
				LEFT JOIN ' . $customer_entity_varchar . ' AS `lastname` ON lastname.`entity_id` = ns.`customer_id` AND lastname.`attribute_id` = ' . ( int ) $attr_lastname . '
				LEFT JOIN ' . $customer_entity_varchar . ' AS `firstname` ON firstname.`entity_id` = ns.`customer_id` AND firstname.`attribute_id` = ' . ( int ) $attr_firstname . '
				WHERE ns.`subscriber_status` = 1
				AND ns.`store_id` = ' . ( int ) $store_id;
			}
		} else if ($list_type == 'C') {
			$add_customer_data = $this->checkIfListWithCustomerData ( $list_type, $store_id );
			if ($add_customer_data) {
				$rq_sql = "SELECT
				c.email,
				cevln.value AS lastname,
				cevfn.value AS firstname,
				MAX(sfo.base_grand_total) AS amount_max_order,
				MIN(sfo.base_grand_total) AS amount_min_order,
				AVG(sfo.base_grand_total) AS amount_avg_order,
				MIN(sfo.created_at) AS date_first_order,
				MAX(sfo.created_at) AS date_last_order,
				COUNT(sfo.entity_id) AS nb_orders,
				SUM(sfo.base_grand_total) AS amount_all_orders,
				(SELECT
					ccev.value AS category
					FROM
					$sales_flat_order AS sfo2
					LEFT JOIN
					$sales_flat_order_item AS sfoi ON sfo2.entity_id = sfoi.order_id
					LEFT JOIN
					$catalog_category_product AS ccp ON ccp.product_id = sfoi.product_id
					LEFT JOIN
					$catalog_category_entity_varchar AS ccev ON ccev.entity_id = ccp.category_id
					AND ccev.attribute_id = 41
					AND ccev.entity_type_id = 3
					WHERE sfo2.customer_id = c.entity_id AND sfo.store_id=$store_id 
					GROUP BY category
					ORDER BY SUM(sfoi.row_total) DESC
					LIMIT 1) AS category
				FROM
				$customer_entity AS c
				LEFT JOIN
				$customer_entity_varchar AS cevfn ON cevfn.entity_id = c.entity_id
				AND cevfn.attribute_id = 5
				LEFT JOIN
				$customer_entity_varchar AS cevln ON cevln.entity_id = c.entity_id
				AND cevln.attribute_id = 7
				LEFT JOIN
				$sales_flat_order AS sfo ON sfo.customer_id = c.entity_id AND c.store_id = sfo.store_id 
				WHERE c.store_id = " . $store_id;
				if ($last_call_date != null && $last_call_date != '') {
					$rq_sql .= " AND (c.created_at > '" . $last_call_date . "' OR c.updated_at > '" . $last_call_date . "' ";
					$rq_sql .= " OR sfo.created_at > '" . $last_call_date . "' OR sfo.updated_at > '" . $last_call_date . "') ";
				}
				$rq_sql .= " GROUP BY c.entity_id ";
				$rq_sql .= " UNION (

					SELECT 
					    sfo.customer_email AS email, sfo.customer_lastname AS lastname,sfo.customer_firstname AS firstname,
					    MAX(sfo.base_grand_total) AS amount_max_order,
					    MIN(sfo.base_grand_total) AS amount_min_order,
					    AVG(sfo.base_grand_total) AS amount_avg_order,
					    MIN(sfo.created_at) AS date_first_order,
					    MAX(sfo.created_at) AS date_last_order,
					    COUNT(sfo.entity_id) AS nb_orders,
					    SUM(sfo.base_grand_total) AS amount_all_orders,
					    (SELECT 
					            ccev.value AS category
					        FROM
								$sales_flat_order AS sfo2
									LEFT JOIN
					            $sales_flat_order_item AS sfoi ON sfo2.entity_id = sfoi.order_id
					                LEFT JOIN
					            $catalog_category_product AS ccp ON ccp.product_id = sfoi.product_id
					                LEFT JOIN
					            $catalog_category_entity_varchar AS ccev ON ccev.entity_id = ccp.category_id
					                AND ccev.attribute_id = 41
					                AND ccev.entity_type_id = 3
								WHERE sfo2.customer_email = sfo.customer_email AND sfo2.customer_is_guest = 1 AND sfo2.store_id = sfo.store_id
					        GROUP BY category
					        ORDER BY SUM(sfoi.row_total) DESC
					        LIMIT 1) AS most_profitable_category
					FROM
					    $sales_flat_order AS sfo
					    WHERE sfo.customer_is_guest = 1 AND sfo.store_id = " . $store_id;
				if ($last_call_date != null && $last_call_date != '') {
					$rq_sql .= " AND (sfo.created_at > '" . $last_call_date . "' OR sfo.updated_at > '" . $last_call_date . "') ";
				}
				$rq_sql .= " GROUP BY sfo.customer_email)";
			} else {
				$rq_sql = " SELECT t.email, t.lastname, t.firstname ";
				$rq_sql .= " FROM ( SELECT c.email AS email,cevln.value AS lastname, cevfn.value AS firstname ";
				$rq_sql .= " FROM $customer_entity c	LEFT JOIN $customer_entity_varchar cevln ON cevln.entity_id = c.entity_id AND cevln.attribute_id=7
				LEFT JOIN $customer_entity_varchar cevfn ON cevfn.entity_id = c.entity_id AND cevfn.attribute_id = 5";
				$rq_sql .= " WHERE c.store_id = " . $store_id;
				if ($last_call_date != null && $last_call_date != '') {
					$rq_sql .= " AND (c.created_at > '" . $last_call_date . "' OR c.updated_at > '" . $last_call_date . "')";
				}
				$rq_sql .= " GROUP BY c.email
						UNION
						SELECT sfo.customer_email AS email ,sfo.customer_lastname as lastname, sfo.customer_firstname as firstname";
				$rq_sql .= " FROM $sales_flat_order sfo ";
				$rq_sql .= " WHERE  sfo.customer_id IS NULL AND sfo.store_id = " . $store_id;
				if ($last_call_date != null && $last_call_date != '') {
					$rq_sql .= " AND (sfo.created_at > '" . $last_call_date . "' OR sfo.updated_at > '" . $last_call_date . "') ";
				}
				$rq_sql .= " GROUP BY email ";
				
				$rq_sql .= " ) as t ";
				$rq_sql .= " GROUP BY t.email;";
			}
		} else {
			return;
		}
		if ($type_action == 'is_updated') {
			$rq_sql .= ' LIMIT 0, 1 ';
			$rq = $db_read->fetchAll ( $rq_sql );
			return count ( $rq );
		} else {
			$rq = $db_read->query ( $rq_sql );
			while ( $r = $rq->fetch () ) {
				$line = $this->dQuote ( $r ['email'] ) . ';'; // TEST
				$line .= $this->dQuote ( $r ['lastname'] ) . ';' . $this->dQuote ( $r ['firstname'] );
				if ($list_type == 'C') {
					if ($add_customer_data) {
						$line .= ';' . $this->dQuote ( $r ['date_first_order'] ) . ';' . $this->dQuote ( $r ['date_last_order'] );
						$line .= ';' . ( float ) $r ['amount_min_order'] . ';' . ( float ) $r ['amount_max_order'] . ';' . ( float ) $r ['amount_avg_order'];
						$line .= ';' . $r ['nb_orders'] . ';' . ( float ) $r ['amount_all_orders'] . ';' . $r ['category'];
					}
				}
				$line .= ';S'."\r\n";
				echo $line;
			}
		}
	}
	private function processNewUnsubscribers($list_type, $store_id, $sd_id, $type_action = 'display', $last_call_date) {
		$resource = Mage::getSingleton ( 'core/resource' );
		$db_read = $resource->getConnection ( 'core_read' );
		
		$sd_updates = $resource->getTableName ( 'sd_updates' );
		
		switch ($list_type) {
			case 'N' :
			case 'C' :
				$rq_sql = 'SELECT email FROM ' . $sd_updates . ' WHERE list_type="' . $list_type . '" AND action = "U"';
				if ($last_call_date != null && $last_call_date != '') {
					$rq_sql .= ' AND update_time > "' . $last_call_date . '"';
				}
				break;
			default :
				return;
		}
		if ($type_action == 'is_updated') {
			$rq = $db_read->fetchAll ( $rq_sql );
			return count ( $rq );
		} else {
			$rq = $db_read->query ( $rq_sql );
			while ( $r = $rq->fetch () ) {
				$line = $this->dQuote ( $r ['email'] ) . ';;'; // TEST
				if ($list_type == 'C') {
					if ($this->checkIfListWithCustomerData ( $list_type, $store_id )) {
						$line .= ';;;;;;;;';
					}
				}
				$line .= ';U' . "\r\n";
				echo $line;
			}
		}
	}
}
