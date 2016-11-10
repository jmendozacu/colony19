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
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_ngram')};
CREATE TABLE {$this->getTable('activo_advancedsearch_ngram')} (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phrase` VARCHAR(150) NOT NULL,
  `frequency` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phrase_idx` (`phrase`),
  KEY `frequency_idx` (`frequency`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('activo_advancedsearch_word')};
CREATE TABLE {$this->getTable('activo_advancedsearch_word')} (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `soundex` VARCHAR(4) NOT NULL,
  `word` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `soundex_idx` (`soundex`),
  UNIQUE KEY `word_idx` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

");
 
$installer->endSetup();

/*
 * Using the logic behind this method:
 * http://codeblow.com/questions/storing-n-grams-in-database-in-n-number-of-tables/
 * 
 * Originally considered but not using it:
 * http://stackoverflow.com/questions/10963316/correct-way-to-store-uni-bi-trigrams-ngrams-in-rdbms
*/
