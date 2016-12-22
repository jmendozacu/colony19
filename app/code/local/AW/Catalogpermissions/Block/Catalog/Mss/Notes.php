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

class AW_Catalogpermissions_Block_Catalog_Mss_Notes extends Varien_Data_Form_Element_Hidden
{
    public function getAfterElementHtml()
    {
        $html = parent::getAfterElementHtml();

        $extensionUrl = 'http://ecommerce.aheadworks.com/magento-extensions/market-segmentation-suite.html';
        $imageSrc = Mage::getBaseUrl('skin') . 'frontend/base/default/images/mss.png';

        $html .= '<table class="form-list" cellspacing="0" id="mss-container">';
        $html .= '<tr>';
        $html .= '<td class="mss-image" style="width: 200px; height: 200px; ">';
        $html .= '<a href="' . $extensionUrl . '" target="_blank">';
        $html .= '<img src="' . $imageSrc . '" alt="MSS"  width="200" height="200" />';
        $html .= '</a>';
        $html .= '</td>';
        $html .= '<td class="mss-notice" style="padding-left: 10px; text-align: justify;">';
        $html .= '<p>';
        $html .= '<a href="' . $extensionUrl . '" target="_blank">Market Segmentation Suite</a> ';
        $html .= 'extension by aheadWorks is not installed.';
        $html .= '</p>';
        $html .= '<p>';
        $html .= 'This extension will let you to hide product or product price from customers matching certain flexible rules.';
        $html .= '</p>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }

}