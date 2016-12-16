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
 * @package    AW_Advancednewsletter
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancednewsletter_Block_Adminhtml_Template_Preview extends Mage_Adminhtml_Block_Newsletter_Template_Preview
{

    protected function _toHtml()
    {
      if ($this->getRequest()->getControllerModule() == $this->getModuleName()) {
          $template = Mage::getModel('advancednewsletter/template');

          if($id = (int)$this->getRequest()->getParam('id')) {
              $template->load($id);
          } else {
              $template->setTemplateType($this->getRequest()->getParam('type'));
              $template->setTemplateText($this->getRequest()->getParam('text'));
              $template->setTemplateStyles($this->getRequest()->getParam('styles'));
          }

          $storeId = (int)$this->getRequest()->getParam('store_id');
          if(!$storeId) {
              $storeId = Mage::app()->getAnyStoreView()->getId();
          }

          Varien_Profiler::start("newsletter_template_proccessing");
          $vars = array();

          $vars['subscriber'] = Mage::getModel('newsletter/subscriber');
          if($this->getRequest()->getParam('subscriber')) {
              $vars['subscriber']->load($this->getRequest()->getParam('subscriber'));
          }

          $template->emulateDesign($storeId);
          $templateProcessed = $template->getProcessedTemplate($vars, true);
          $template->revertDesign();

          if($template->isPlain()) {
              $templateProcessed = "<pre>" . htmlspecialchars($templateProcessed) . "</pre>";
          }

          Varien_Profiler::stop("newsletter_template_proccessing");

          return $templateProcessed;
      }
      
      return parent::_toHtml();
    }
}
