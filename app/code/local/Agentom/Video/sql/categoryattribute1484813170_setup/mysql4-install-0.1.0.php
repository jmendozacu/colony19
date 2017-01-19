<?php
$installer = $this;
$installer->startSetup();


$installer->addAttribute("catalog_category", "hidden_from_customer",  array(
    "type"     => "int",
    "backend"  => "",
    "frontend" => "",
    "label"    => "Cachée pour les clients",
    "input"    => "select",
    "class"    => "",
    "source"   => "eav/entity_attribute_source_boolean",
    "global"   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible"  => true,
    "required" => true,
    "user_defined"  => false,
    "default" => "0",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
	
    "visible_on_front"  => false,
    "unique"     => false,
    "note"       => "Cache la catégorie si non achetée"

	));
$installer->endSetup();
	 