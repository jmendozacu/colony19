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
$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_query')};
CREATE TABLE {$this->getTable('activo_advancedsearch_query')} (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Query ID',  
  `query_text` text COMMENT 'Query text',
  `store_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT 'Store ID',
  `created_at` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Created at',
  PRIMARY KEY (`id`)  
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_query_result')};
CREATE TABLE {$this->getTable('activo_advancedsearch_query_result')} (
  `query_id` int(10) unsigned NOT NULL COMMENT 'Query ID',
  `popularity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Popularity',
  `unique_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Unique Query Text Count',
  `updated_at` date NOT NULL DEFAULT '0000-00-00' COMMENT 'Updated at',  
  KEY `FK_QUERY_ID` (`query_id`),
  CONSTRAINT `FK_QUERY_ID` FOREIGN KEY (`query_id`) REFERENCES {$this->getTable('activo_advancedsearch_query')} (`id`) ON DELETE CASCADE ON UPDATE CASCADE  
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

");

$installer->endSetup();
