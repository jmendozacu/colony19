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
 * @package    AW_Zblocks
 * @version    2.5.2
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'zblocks';
        $this->_controller = 'adminhtml_zblocks';

        $this->_updateButton('save', 'label', $this->__('Save Block'));
        $this->_updateButton('delete', 'label', $this->__('Delete Block'));

        $this->_addButton('saveandcontinue', array(
                'label'   => $this->__('Save And Continue Edit'),
                'onclick' => 'saveAndContinueEdit()',
                'class'   => 'save',
        ), -100);

        if ($this->getRequest()->getParam('id')) {
            $this->_addButton(
                'saveandduplicate',
                array(
                    'label'   => $this->__('Save And Duplicate'),
                    'onclick' => 'awSaveAndDuplicate()',
                    'class'   => 'save',
                    'id'      => 'zblocks-save-and-duplicate'
                ),
                0
            );
        }

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('zblocks_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'zblocks_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'zblocks_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/tab/'+zblocks_tabsJsTabs.activeTab.id);
            }

            function awSaveAndDuplicate() {
                if($('edit_form').action.indexOf('duplicate/1/')<0) {
                    $('edit_form').action += 'duplicate/1/';
                    $('edit_form').action += 'back/edit/tab/'+zblocks_tabsJsTabs.activeTab.id;
                }
                editForm.submit();
            }

            var aw_zblocks_categories = ['" . implode('\', \'', Mage::getModel('zblocks/source_position')->getNeedCategoryPositions()) . "'];
            var aw_zblocks_products = ['" . implode('\', \'', Mage::getModel('zblocks/source_position')->getNeedProductPositions()) . "'];
        ";
    }

    public function getHeaderText()
    {
        $data = Mage::registry('zblocks_data');
        return isset($data['block_title'])
            ? $this->__('Edit Block \'%s\'', $this->htmlEscape($data['block_title']))
            : $this->__('Add Block');
    }
}
