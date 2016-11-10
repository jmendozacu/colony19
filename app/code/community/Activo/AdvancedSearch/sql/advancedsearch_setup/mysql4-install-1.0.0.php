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
DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_word')};
CREATE TABLE {$this->getTable('activo_advancedsearch_word')} (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `soundex` VARCHAR(4) NOT NULL,
  `word` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `soundex_idx` (`soundex`),
  UNIQUE KEY `word_idx` (`word`(10))
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_dictionary')};
CREATE TABLE {$this->getTable('activo_advancedsearch_dictionary')} (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `num_products` INT(10) UNSIGNED NOT NULL,
  `num_words` INT(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  `modified_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");
 
$installer->endSetup();
