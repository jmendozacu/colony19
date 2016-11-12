<?php
$installer = $this;
$installer->startSetup();
 
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
 

 
$setup->addAttribute('customer', 'genre', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Civilité',
        'visible'       => 1,
        'required'      => 1,
        'user_defined'  => 1,
));

$setup->addAttribute('customer', 'sociale', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Raison sociale',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));

$setup->addAttribute('customer_address', 'etage', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Étage, Escalier, Appartement',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));

$setup->addAttribute('customer_address', 'batiment', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Batiment, Immeuble, Résidence',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));

$setup->addAttribute('customer_address', 'interphone', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Interphone',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));

$setup->addAttribute('customer_address', 'porte', array(
        'input'         => 'text',
        'type'          => 'text',
        'label'         => 'Porte',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));

$setup->addAttribute('customer_address', 'message', array(
        'input'         => 'textarea',
        'type'          => 'text',
        'label'         => 'Message',
        'visible'       => 1,
        'required'      => 0,
        'user_defined'  => 1,
));
 


Mage::getSingleton('eav/config')
	->getAttribute('customer', 'genre')
	->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer', 'sociale')
	->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer_address', 'etage')
	->setData('used_in_forms', array('adminhtml_customer_address','customer_account_create','customer_address_edit','checkout_register','customer_register_address'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer_address', 'batiment')
	->setData('used_in_forms', array('adminhtml_customer_address','customer_account_create','customer_address_edit','checkout_register','customer_register_address'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer_address', 'interphone')
	->setData('used_in_forms', array('adminhtml_customer_address','customer_account_create','customer_address_edit','checkout_register','customer_register_address'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer_address', 'porte')
	->setData('used_in_forms', array('adminhtml_customer_address','customer_account_create','customer_address_edit','checkout_register','customer_register_address'))
	->save();
Mage::getSingleton('eav/config')
	->getAttribute('customer_address', 'message')
	->setData('used_in_forms', array('adminhtml_customer_address','customer_account_create','customer_address_edit','checkout_register','customer_register_address'))
	->save();
	
$installer->run("
	ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `genre` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `sociale` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `etage` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `batiment` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `interphone` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `porte` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL;
    ALTER TABLE {$this->getTable('sales_flat_quote_address')} ADD COLUMN `message` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL; 
	
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `genre` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `sociale` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
	ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `etage` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `batiment` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL ;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `interphone` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `porte` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL;
    ALTER TABLE {$this->getTable('sales_flat_order_address')} ADD COLUMN `message` VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL;
    ");
	


$installer->endSetup();

