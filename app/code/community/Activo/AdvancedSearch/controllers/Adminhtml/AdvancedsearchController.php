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


class Activo_AdvancedSearch_Adminhtml_AdvancedsearchController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog/advancedsearch')
            ->_addBreadcrumb(Mage::helper('catalog')->__('Catalog'), Mage::helper('catalog')->__('Catalog'))
            ->_addBreadcrumb(Mage::helper('advancedsearch')->__('Advanced Search'), Mage::helper('advancedsearch')->__('Advanced Search'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('advancedsearch/adminhtml_advancedsearch'))
            ->renderLayout();
    }

 
    public function builddictionaryAction()
    {
        Mage::getModel('advancedsearch/dictionary')->build();
        
        $this->indexAction();
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/advancedsearch');
    }
}
