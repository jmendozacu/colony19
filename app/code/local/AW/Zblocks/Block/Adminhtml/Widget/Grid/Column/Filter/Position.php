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
 * @version    2.5.4
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Zblocks_Block_Adminhtml_Widget_Grid_Column_Filter_Position extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Select
{
    protected function _getOptions()
    {
        $emptyOption = array('value' => null, 'label' => '');
        $colOptions = $this->getColumn()->getOptions();
        if (!empty($colOptions) && is_array($colOptions) ) {
            $options = array($emptyOption);
            foreach ($colOptions as $optionGroup) {
                if (!isset($optionGroup['value']) || !isset($optionGroup['label'])) {
                    continue;
                }
                $options[] = array('value' => $optionGroup['value'], 'label' => $optionGroup['label']);
            }
            return $options;
        }
        return array();
    }

    public function getHtml()
    {
        $html = '<select name="'.$this->_getHtmlName().'" id="'.$this->_getHtmlId().'" class="no-changes">';
        $value = $this->getValue();
        foreach ($this->_getOptions() as $option){
            if (!is_array($option)) {
                $html.= $this->_optionToHtml(array(
                        'value' => $option['value'],
                        'label' => $option['label']),
                    $value
                );
            }
            elseif (is_array($option['value'])) {
                $html.='<optgroup label="'.$option['label'].'">'."\n";
                foreach ($option['value'] as $groupItem) {
                    $html.= $this->_optionToHtml($groupItem, $value);
                }
                $html.='</optgroup>'."\n";
            }
            else {
                $html.= $this->_optionToHtml($option, $value);
            }
        }
        $html.='</select>';
        return $html;
    }

    protected function _optionToHtml($option, $value)
    {
        if (is_array($option['value'])) {
            $html ='<optgroup label="'.$option['label'].'">'."\n";
            foreach ($option['value'] as $groupItem) {
                $html .= $this->_optionToHtml($groupItem, $value);
            }
            $html .='</optgroup>'."\n";
        }
        else {
            $html = '<option value="'.$option['value'].'"';
            $selected = '';
            if ($value === $option['value']) {
                $selected = 'selected';
            }
            $html.= $selected;
            $html.= '>'.$option['label']. '</option>'."\n";
        }
        return $html;
    }
}