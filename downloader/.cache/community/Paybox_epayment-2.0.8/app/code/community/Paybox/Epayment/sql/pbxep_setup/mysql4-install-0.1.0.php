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

// Initialization
$installer = $this;
$installer->startSetup();

$catalogEav = Mage::getResourceModel('catalog/eav_mysql4_setup', 'core_setup');

$defs = array(
	'pbxep_action' => array(
		'type' => 'varchar',
	),
	'pbxep_delay' => array(
		'type' => 'varchar',
	),
	'pbxep_authorization' => array(
		'type' => 'text',
	),
	'pbxep_capture' => array(
		'type' => 'text',
	),
	'pbxep_first_payment' => array(
		'type' => 'text',
	),
	'pbxep_second_payment' => array(
		'type' => 'text',
	),
	'pbxep_third_payment' => array(
		'type' => 'text',
	),
	'pbxep_fourth_payment' => array(
		'type' => 'text',
	),
);

$entity = 'order_payment';

foreach ($defs as $name => $def) {
	$installer->addAttribute('order_payment', $name, $def);
}

// Required tables
$statusStateTable = $installer->getTable('sales/order_status_state');

 // Insert statuses
$installer->getConnection()->insertArray(
    $statusTable,
    array(
        'status',
        'label'
    ),
    array(
        array('status' => 'pbxep_partiallypaid', 'label' => 'Paid Partially'),
    )
);
 
// Insert states and mapping of statuses to states
$installer->getConnection()->insertArray(
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
);

// Finalization
$installer->endSetup();