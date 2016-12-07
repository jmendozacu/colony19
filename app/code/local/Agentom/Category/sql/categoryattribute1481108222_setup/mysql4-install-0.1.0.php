<?php
$installer = $this;
$installer->startSetup();


$installer->addAttribute("catalog_category", "logo_menu",  array(
    "group" => "General Information",
    "type"     => "varchar",
    "backend"  => "catalog/category_attribute_backend_image",
    "frontend" => "",
    "label"    => "Logo du menu",
    "input"    => "image",
    "class"    => "",
    "source"   => "",
    "global"   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    "visible"  => true,
    "required" => false,
    "user_defined"  => false,
    "default" => "",
    "searchable" => false,
    "filterable" => false,
    "comparable" => false,
	
    "visible_on_front"  => true,
    "unique"     => false,
    "note"       => ""

	));
$installer->endSetup();
	 