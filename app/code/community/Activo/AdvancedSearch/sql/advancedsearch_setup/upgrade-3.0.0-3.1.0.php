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

$connection->dropTable($installer->getTable('advancedsearch/ngram'));

$table = $installer->getConnection()
    ->newTable($installer->getTable('advancedsearch/ngram'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Store ID')
    ->addColumn('phrase', Varien_Db_Ddl_Table::TYPE_VARCHAR, 180, array(
        'nullable'  => false,
        ), 'Phrase')
    ->addColumn('frequency', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'nullable'  => false,
        ), 'Frequency')
    ->addIndex(
        $installer->getIdxName(
            'advancedsearch/ngram',
            array('store_id', 'phrase'),
            Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
        ),
        array('store_id', 'phrase'),
        array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE))
    ->addIndex($installer->getIdxName('advancedsearch/ngram', array('frequency')),
        array('frequency'))
    ->setComment('Activo Search Autocomplete Table');
$installer->getConnection()->createTable($table);

Mage::getSingleton('index/indexer')->getProcessByCode('advancedsearch_complete')
        ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);