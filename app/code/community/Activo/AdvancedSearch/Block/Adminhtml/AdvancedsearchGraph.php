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
class Activo_AdvancedSearch_Block_Adminhtml_AdvancedsearchGraph extends Mage_Core_Block_Template
{

    public function __construct()
    {
        $this->setTemplate('activo/advancedsearch/advancedsearch_graph.phtml');
    }

    public function getGraphData()
    {
        return Mage::getModel('advancedsearch/query')->getGraphData();
    }

}
