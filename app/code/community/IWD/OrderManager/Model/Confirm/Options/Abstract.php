<?php

abstract class IWD_OrderManager_Model_Confirm_Options_Abstract
{
    public abstract function toOption();

    public function toOptionArray()
    {
        $optionsArray = array();

        $options = $this->toOption();
        foreach ($options as $value => $label) {
            $optionsArray[] = array('value' => $value, 'label' => $label);
        }

        return $optionsArray;
    }
}
