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


class AW_Zblocks_Block_Adminhtml_Widget_Grid_Column_Renderer_Position extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Options
{
    public function render(Varien_Object $row)
    {
        $value = $row->getData('position');
        $html = '';
        $positions = Mage::getModel('zblocks/source_position')->toOptionArray();

        foreach ($positions as $position) {
            if (!isset($position['value']) || !is_array($position['value'])) {
                continue;
            }
            foreach ($position['value'] as $option) {
                $key = array_search($value, $option);
                if (!$key) {
                    continue;
                }
                $html .= $option['label'];
            }
        }

        return $html;
    }
}