<?php
/**
 * MageWorx
 * MageWorx SeoMarkup Extension
 *
 * @category   MageWorx
 * @package    MageWorx_SeoMarkup
 * @copyright  Copyright (c) 2015 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_SeoMarkup_Helper_Json_Seller extends Mage_Core_Helper_Abstract
{
    protected $_product;

    public function getJsonOrganizationData()
    {
        if (!Mage::helper('mageworx_seomarkup/config')->isSellerRichsnippetEnabled()) {
            return false;
        }

        $name = Mage::helper('mageworx_seomarkup/config')->getSellerName();
        $image = $this->getSellerImageUrl();

        if (!$name || !$image) { // Name and Image are required fields
            return false;
        }

        $data = array();
        $data['@context']    = 'http://schema.org';
        $data['@type']       = Mage::helper('mageworx_seomarkup/config')->getSellerType();

        if ($name) {
            $data['name'] = $name;
        }

        $description = Mage::helper('mageworx_seomarkup/config')->getSellerDescription();
        if ($description) {
            $data['description'] = $description;
        }

        $phone = Mage::helper('mageworx_seomarkup/config')->getSellerPhone();
        if ($phone) {
            $data['telephone'] = $phone;
        }

        $email = Mage::helper('mageworx_seomarkup/config')->getSellerEmail();
        if ($email) {
            $data['email'] = $email;
        }

        $fax = Mage::helper('mageworx_seomarkup/config')->getSellerFax();
        if ($fax) {
            $data['faxNumber'] = $fax;
        }

        $address = $this->_getAddress();
        if ($address && count($address) > 1) {
            $data['address'] = $address;
        }

        $socialLinks = Mage::helper('mageworx_seomarkup/config')->getSameAsLinks();

        if(is_array($socialLinks) && !empty($socialLinks)){
            $data['sameAs'] = array();
            $data['sameAs'][] = $socialLinks;
        }

        $data['url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        if ($image) {
            $data['image'] = $image;
        }

        $priceRange = Mage::helper('mageworx_seomarkup/config')->getSellerPriceRange();
        if ($priceRange) {
            $data['priceRange'] =  $priceRange;
        }
        return $data;
    }

    protected function _getAddress()
    {
        $data = array();
        $data['@type']           = 'PostalAddress';
        $data['addressLocality'] = Mage::helper('mageworx_seomarkup/config')->getSellerLocation();
        $data['addressRegion']   = Mage::helper('mageworx_seomarkup/config')->getSellerRegionAddress();
        $data['streetAddress']   = Mage::helper('mageworx_seomarkup/config')->getSellerStreetAddress();
        $data['postalCode']      = Mage::helper('mageworx_seomarkup/config')->getSellerPostCode();
        return $data;
    }

    public function getSellerImageUrl()
    {
        $folderName = MageWorx_SeoMarkup_Model_System_Config_Backend_SellerImage::UPLOAD_DIR;
        $storeConfig = Mage::helper('mageworx_seomarkup/config')->getSellerImage();
        $faviconFile = Mage::getBaseUrl('media') . $folderName . '/' . $storeConfig;
        $absolutePath = Mage::getBaseDir('media') . '/' . $folderName . '/' . $storeConfig;

        if(!is_null($storeConfig) &&  Mage::helper('mageworx_seomarkup')->isFile($absolutePath)) {
            return $faviconFile;
        }
        return false;
    }
}