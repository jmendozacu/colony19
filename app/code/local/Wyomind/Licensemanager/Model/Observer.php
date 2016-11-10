<?php

class Wyomind_Licensemanager_Model_Observer {

    public function saveConfig($observer) {

        $namespace = $observer->getEvent()->getObject()->getSection();


        if (Mage::helper('core')->isModuleEnabled('Wyomind_' . ucfirst($namespace))) {
            
            $domain = Mage::getStoreConfig("web/secure/base_url");
            $before_activation_code = Mage::getStoreConfig("$namespace/license/activation_code");
            $before_activation_key = Mage::getStoreConfig("$namespace/license/activation_key");
            $post = (Mage::app()->getRequest()->getPost());
            $after_activation_key = $post["groups"]["license"]["fields"]["activation_key"]["value"];
            if (isset($post["groups"]["license"]["fields"]["activation_code"]["value"])) {
                $after_activation_code = $post["groups"]["license"]["fields"]["activation_code"]["value"];
            } else {
                $after_activation_code = "N/A";
            }

            $registered_version = Mage::getStoreConfig("$namespace/license/version");
            if ($before_activation_key != $after_activation_key) {
                Mage::helper("licensemanager")->log($namespace, $registered_version, $domain, null, 'update activation key -> from ' . $before_activation_key . ' to ' . $after_activation_key);
            }
            if ($before_activation_code != $after_activation_code) {
                Mage::helper("licensemanager")->log($namespace, $registered_version, $domain, null, 'update license code -> from ' . $before_activation_code . ' to ' . $after_activation_code);
            }
        }
    }

}
