<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Catalogpermissions
 * @version    1.4.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


$setup = $this;
$setup->startSetup();

$setup->addAttribute(
    'catalog_product',
    AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRODUCT,
    array(
        'backend' => 'catalogpermissions/entity_attribute_backend_segments',
        'frontend' => 'catalogpermissions/product_attribute_frontend_mssAttr',
        'source' => 'catalogpermissions/entity_attribute_source_segments',
        'group' => 'Permissions',
        'label' => 'Hide product for users from MSS segment',
        'input' => 'select',
        'class' => '',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'type' => 'int',
        'visible' => 1,
        'user_defined' => false,
        'default' => '',
        'apply_to' => '',
        'visible_on_front' => false,
        'required' => false,
        'unique' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
    )
);

$setup->addAttribute(
    'catalog_product',
    AW_Catalogpermissions_Helper_Data::MSS_DISABLE_PRICE,
    array(
        'backend' => 'catalogpermissions/entity_attribute_backend_segments',
        'frontend' => 'catalogpermissions/product_attribute_frontend_mssAttr',
        'source' => 'catalogpermissions/entity_attribute_source_segments',
        'group' => 'Permissions',
        'label' => 'Hide product price for users from MSS segment',
        'input' => 'select',
        'class' => '',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'type' => 'int',
        'visible' => 1,
        'user_defined' => false,
        'default' => '',
        'apply_to' => '',
        'visible_on_front' => false,
        'required' => false,
        'unique' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
    )
);

$setup->addAttribute(
    'catalog_product',
    AW_Catalogpermissions_Helper_Data::MSS_INFO,
    array(
        'group' => 'Permissions',
        'input' => 'hidden',
        'class' => '',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'input_renderer' => 'catalogpermissions/catalog_mss_notes',
        'type' => 'int',
        'visible' => 0,
        'user_defined' => false,
        'default' => '',
        'apply_to' => '',
        'visible_on_front' => false,
        'required' => false,
        'unique' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
    )
);

$setup->addAttribute(
    'catalog_category',
    AW_Catalogpermissions_Helper_Data::MSS_DISABLE_CATEGORY,
    array(
        'backend' => 'catalogpermissions/entity_attribute_backend_segments',
        'frontend' => 'catalogpermissions/product_attribute_frontend_mssAttr',
        'source' => 'catalogpermissions/entity_attribute_source_segments',
        'group' => 'Permissions',
        'label' => 'Hide category for users from MSS segment',
        'input' => 'select',
        'class' => '',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'type' => 'int',
        'visible' => 1,
        'user_defined' => false,
        'default' => '',
        'apply_to' => '',
        'visible_on_front' => false,
        'required' => false,
        'unique' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
    )
);

$setup->addAttribute(
    'catalog_category',
    AW_Catalogpermissions_Helper_Data::MSS_INFO,
    array(
        'group' => 'Permissions',
        'input' => 'hidden',
        'class' => '',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'input_renderer' => 'catalogpermissions/catalog_mss_notes',
        'type' => 'int',
        'visible' => 0,
        'user_defined' => false,
        'default' => '',
        'apply_to' => '',
        'visible_on_front' => false,
        'required' => false,
        'unique' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
    )
);

$setup->endSetup();
