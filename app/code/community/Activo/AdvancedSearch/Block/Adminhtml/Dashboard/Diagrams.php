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
class Activo_AdvancedSearch_Block_Adminhtml_Dashboard_Diagrams extends Mage_Adminhtml_Block_Dashboard_Diagrams
{

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addTab('advancedsearch_graph', array(
            'label' => $this->__('Advanced Search Graph'),
            'content' => $this->getLayout()->createBlock('Activo_AdvancedSearch_Block_Adminhtml_AdvancedsearchGraph')->toHtml(),
            'active' => true
        ));
    }

}
