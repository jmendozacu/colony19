<?php
$installer = $this;
$installer->startSetup();


$installer->addAttribute("customer", "allowed_category_ids",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Catégories autorisées",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default" => "",
    "frontend" => "",
    "unique"     => false,
    "note"       => "Liste des id catégories autorisées suite à l'achat"

	));

        $attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "allowed_category_ids");

        
$used_in_forms=array();

$used_in_forms[]="adminhtml_customer";
        $attribute->setData("used_in_forms", $used_in_forms)
		->setData("is_used_for_customer_segment", true)
		->setData("is_system", 0)
		->setData("is_user_defined", 1)
		->setData("is_visible", 0)
		->setData("sort_order", 100)
		;
        $attribute->save();
	
	
	
$installer->endSetup();
	 