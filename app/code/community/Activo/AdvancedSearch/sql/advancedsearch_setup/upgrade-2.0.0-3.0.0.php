<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

/** @var $installer Mage_Catalog_Model_Resource_Setup */
$installer  = $this;
$connection = $installer->getConnection();

/**
 * Create table 'advancedsearch/weighted_search' - weighted search index
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('advancedsearch/weighted_search'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'ID')
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
        ), 'Entity ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Store Id')
    ->addColumn('weight', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '1',
        ), 'Weight')
    ->addColumn('data_index', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        'default'   => '',
        ), 'Data')
    ->addIndex($installer->getIdxName('advancedsearch/weighted_search', array('entity_id')),
        array('entity_id'))
    ->addIndex($installer->getIdxName('advancedsearch/weighted_search', array('store_id')),
        array('store_id'))
    ->setComment('Advanced Search Weighted Attribute Index Table');

$installer->getConnection()->createTable($table);