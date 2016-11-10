<?php
/**
 * Paybox Epayment module for Magento
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * available at : http://opensource.org/licenses/osl-3.0.php
 *
 * @package    Paybox_Epayment
 * @copyright  Copyright (c) 2013-2014 Paybox
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

// Required tables
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');

 // Insert statuses
if(!$installer->getConnection()->insertArray(
	$statusTable,
	array(
		'status',
		'label'
	),
	array(
		array('status' => 'pbxep_partiallypaid', 'label' => 'Paid Partially'),
	)
)){	
	$this->logDebug('E-Transactions status install failed');
}
 
// Insert states and mapping of statuses to states
if(!$installer->getConnection()->insertArray(
	$statusStateTable,
	array(
		'status',
		'state',
		'is_default'
	),
	array(
		array(
			'status' => 'pbxep_partiallypaid',
			'state' => 'processing',
			'is_default' => 1
		),
	)
)){	
	$this->logDebug('E-Transactions StatusState install failed');
}

// Finalization
$installer->endSetup();