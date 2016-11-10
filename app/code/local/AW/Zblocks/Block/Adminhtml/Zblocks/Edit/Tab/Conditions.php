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


class AW_Zblocks_Block_Adminhtml_Zblocks_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('conditions_');

        $this
            ->_addCategoriesFieldsetToForm($form)
            ->_addFilterFieldsetToForm($form)
            ->_addCmsFieldsetToForm($form);

        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _addFilterFieldsetToForm($form)
    {
        if($this->getRequest()->getParam('id')) {
            $model = Mage::getModel('zblocks/condition')->load((int) $this->getRequest()->getParam('id'), 'zblock_id');
        } else {
            $model = Mage::getModel('zblocks/condition');
        }

        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('*/adminhtml_zblocks/newConditionHtml/form/conditions_product_filter_fieldset'));

        $fieldset = $form->addFieldset('product_filter_fieldset', array(
                'legend'=>Mage::helper('zblocks')->__('Conditions (leave blank for all products)'))
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
                'name' => 'conditions',
                'label' => Mage::helper('zblocks')->__('Conditions'),
                'title' => Mage::helper('zblocks')->__('Conditions'),
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('zblocks/adminhtml_widget_form_renderer_conditions'));

        $filterNotAvailableMessage = $this->__('Products filter is not applicable for selected block position');
        $fieldset->addField('product_filter_warning', 'hidden', array(
                'after_element_html' => "<div id='awzb_products_error'>{$filterNotAvailableMessage}</div>"
        ));

        $form->addValues($model->getData());

        return $this;
    }

    protected function _addCmsFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('cms_fieldset', array('legend' => $this->__('CMS Pages')));

        $fieldset->addField('show_in_cms', 'select', array(
                'name' => 'show_in_cms',
                'label' => $this->__('Show in CMS pages'),
                'title' => $this->__('Show in CMS pages'),
                'value' => '1',
                'options' => array(
                    '1' => $this->__('Yes'),
                    '0' => $this->__('No'),
                ),
        ));

        if ($data = Mage::registry('zblocks_data')) {
            $form->addValues($data);
        }

        return $this;
    }

    protected function _addCategoriesFieldsetToForm($form)
    {
        $fieldset = $form->addFieldset('categories_fieldset', array('legend' => $this->__('Categories')));

        $categoriesBlock = Mage::getSingleton('core/layout')
            ->createBlock('zblocks/adminhtml_zblocks_edit_tab_conditions_categories')
        ;

        $fieldset->addField('gridcontainer_categories', 'hidden', array(
            'label' => $this->__('Categories'),
            'title' => $this->__('Categories'),
            'after_element_html'  => $categoriesBlock->toHtml()
        ));

        $form->addValues($categoriesBlock->getProduct());

        return $this;
    }
}
