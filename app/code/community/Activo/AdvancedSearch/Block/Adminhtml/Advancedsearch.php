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

class Activo_AdvancedSearch_Block_Adminhtml_Advancedsearch extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize grid container settings
     *
     */
    public function __construct()
    {
        $this->_blockGroup      = 'advancedsearch';
        $this->_controller      = 'adminhtml_advancedsearch';
        $this->_headerText      = Mage::helper('advancedsearch')->__('Advanced Search');

        parent::__construct();
        
        $this->removeButton('add');

        $this->_addButton('builddictionary',array(
            'label'     => 'Build Dictionary',
            'onclick'   => 'setLocation(\'' . $this->getUrl('*/*/builddictionary') .'\')',
            'class'     => 'add',
        ));

        
    }
}
