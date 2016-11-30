<?php

class IWD_OrderManager_Varien_Data_Form_Element_Thumbnail extends Varien_Data_Form_Element_Abstract
{
    public function __construct($data)
    {
        parent::__construct($data);
        $this->setType('file');
    }

    public function getElementHtml()
    {
        $html = '';

        if ($this->getValue()) {
            $html = '<img id="' . $this->getHtmlId() . '_image" title="' . $this->getValue() . '"'
                    . 'class="margin-left" style="display:block;margin-bottom:15px;height:34px; max-width:90px;"'
                    . 'src="' . $this->getValue() . '" alt="' . $this->getValue() . '">';
        }

        $this->setClass('input-file margin-left');
        if (!$this->getValue() && $this->getRequired()) {
            $this->addClass('required-entry');
        }

        return $html . parent::getElementHtml();
    }

    protected function _getUrl()
    {
        return $this->getValue();
    }

    public function getName()
    {
        return $this->getData('name');
    }
}
