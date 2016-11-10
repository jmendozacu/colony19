<?php
/**
 * Activo Extensions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Activo Commercial License
 * that is available through the world-wide-web at this URL:
 * http://extensions.activo.com/license_professional
 *
 * @copyright   Copyright (c) 2016 Activo Extensions (http://extensions.activo.com)
 * @license     Commercial
 */

/**
 * MinSaleQty value manipulation helper
 */
class Activo_AdvancedSearch_Helper_Weightedattr
{
    /**
     * Retrieve fixed weight value
     *
     * @param mixed $weight
     * @return int|null
     */
    protected function _fixQty($weight)
    {
        if (!empty($weight) && $weight > 0 && $weight <= 1000) {
            return (int)$weight;
        } else {
            return null;
        }
    }

    /**
     * Generate a storable representation of a value
     *
     * @param mixed $value
     * @return string
     */
    protected function _serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (int)$value;
            return (string)$data;
        } else if (is_array($value)) {
            $data = array();
            foreach ($value as $code => $weight) {
                if (!array_key_exists($code, $data)) {
                    $data[$code] = $this->_fixQty($weight);
                }
            }
            return serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param mixed $value
     * @return array
     */
    protected function _unserializeValue($value)
    {
        if (is_string($value) && !empty($value)) {
            return unserialize($value);
        } else {
            return array();
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param mixed
     * @return bool
     */
    protected function _isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('searchable_attr_code', $row) || !array_key_exists('weight', $row)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param array
     * @return array
     */
    protected function _encodeArrayFieldValue(array $value)
    {
        $result = array();
        foreach ($value as $code => $weight) {
            $_id = Mage::helper('core')->uniqHash('_');
            $result[$_id] = array(
                'searchable_attr_code' => $code,
                'weight' => $this->_fixQty($weight),
            );
        }
        return $result;
    }

    /**
     * Decode value from used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param array
     * @return array
     */
    protected function _decodeArrayFieldValue(array $value)
    {
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('searchable_attr_code', $row) || !array_key_exists('weight', $row)) {
                continue;
            }
            $code = $row['searchable_attr_code'];
            $weight = $this->_fixQty($row['weight']);
            $result[$code] = $weight;
        }
        return $result;
    }

    /**
     * Retrieve weighted attribute value from config
     *
     * @param mixed $store
     * @return float|null
     */
    public function getConfigValue($store = null)
    {
        $value = Mage::getStoreConfig(Activo_AdvancedSearch_Model_Dictionary::XML_PATH_SERP_WEIGHTED_ATTRIBUTES, $store);
        $value = $this->_unserializeValue($value);
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }

        return $value;
    }
    
    public function isWeightedAttributesEnabled()
    {
        $weightedAttr = $this->getConfigValue();
        return (!empty($weightedAttr));
    }

    /**
     * Make value readable by Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param mixed $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->_unserializeValue($value);
        if (!$this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param mixed $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->_isEncodedArrayFieldValue($value)) {
            $value = $this->_decodeArrayFieldValue($value);
        }
        $value = $this->_serializeValue($value);
        return $value;
    }
}
