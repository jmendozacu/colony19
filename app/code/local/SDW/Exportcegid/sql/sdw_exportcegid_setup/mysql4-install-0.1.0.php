<?php

$installer = $this;
$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);

$setup->removeAttribute('customer', "numero_compte_client");
$setup->removeAttributeGroup('customer', $attributeSetId, "Export comptable");


$installer->addAttributeGroup($entityTypeId, $attributeSetId, "Export comptable", 100);
$attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSetId, "Export comptable");

$installer->addAttribute("customer", "numero_compte_client",  array(
    "type"     => "varchar",
    "backend"  => "",
    "label"    => "Numéro compte client",
    "input"    => "text",
    "source"   => "",
    "visible"  => true,
    "required" => false,
    "default"  => "",
    "frontend" => "",
    "unique"   => false,
    "group"    => "Export comptable"
));

$attribute   = Mage::getSingleton("eav/config")->getAttribute("customer", "numero_compte_client");
$setup->addAttributeToGroup($entityTypeId,$attributeSetId,$attributeGroupId,'numero_compte_client','999');

$attribute->setData("used_in_forms", array(
        "adminhtml_customer"
    ))
    ->setData("is_used_for_customer_segment", true)
    ->setData("is_system", 0)
    ->setData("is_user_defined", 1)
    ->setData("is_visible", 1)
    ->setData("sort_order", 100);
$attribute->save();


$installer->endSetup();