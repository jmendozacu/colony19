<?php

class Activo_AdvancedSearch_Model_Adminhtml_System_Source_Crontimes
{

    /**
     * Fetch options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        $times = array();
        for ($i = 1; $i <= 24; $i++)
        {
            for ($j = 0; $j <= 60; $j+=5)
            {
                $times[] = array(  'label' => sprintf('%1$02d:%2$02d',$i,$j),
                                'value' => sprintf('%d %d * * *',$j,$i));
            }
        }
        return $times;
    }
}
