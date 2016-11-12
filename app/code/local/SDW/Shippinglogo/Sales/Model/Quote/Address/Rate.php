<?php


class SDW_Shippinglogo_Sales_Model_Quote_Address_Rate extends Mage_Sales_Model_Quote_Address_Rate
{
    public function getLogo()
    {
        $url=false;
        
        $logo = Mage::getStoreConfig("carriers/".$this->getCarrier()."/logo");
        $imageFilepath = "shippinglogo" . DS . $logo;
        if($logo && file_exists(Mage::getBaseDir('media').DS.$imageFilepath) )
        {
            $url=Mage::getBaseUrl('media').$imageFilepath;
        }
        
        return $url;
    }
}