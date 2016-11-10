<?php
/**
 * Copyright (c) 2013-2015 Man4x
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @project     Magento Man4x Mondial Relay Module
 * @description Generic helper         
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse man4x[@]hotmail[.fr]
 */

class Man4x_MondialRelay_Helper_Data
    extends Mage_Core_Helper_Abstract
{

    const CSV_EOL = "\r\n";
   
    // Phone international prefix
    protected $_phoneIntPrefix = array(
        'NL' => '31',
        'BE' => '32',
        'FR' => '33',
        'ES' => '34',
        'LU' => '352',
        'AD' => '376',
        'MC' => '377',
        'DE' => '49',
        'PT' => '351',
    );

    // Phone international prefix (c = cellular)
    // Some of these formats are country-level rules, others are regex defined by Mondial Relay
    // See doc file for suggestions with phone regex
    protected $_phoneRegex = array(
        'DEF'   => array('/^(00|\+)?[0-9]{6,13}$/', '/^[0-9]{6,10}$/'),
        'FR'    => array('/^(00|\+)?33[12345][0-9]{8}$/', '/^0[12345][0-9]{8}$/'),
        'FRc'   => array('/^(00|\+)?33[67][0-9]{8}$/', '/^0[67][0-9]{8}$/'),
        'BE'    => array('/^(00|\+)?32[1-9][0-9]{7}$/', '/^0[1-9][0-9]{7}$/'),
        'BEc'   => array('/^(00|\+)?324[6789][0-9]{7}$/', '/^04[6789][0-9]{7}$/'),
        'LU'    => array('/^(00|\+)?352[0-9]{6,10}$/', '/^[0-9]{6,10}$/'),
        'ES'    => array('/^(00|\+)?349[0-9]{8}$/', '/^9[0-9]{8}$/'),
        'ESc'   => array('/^(00|\+)?34[67][0-9]{8}$/', '/^[67][0-9]{8}$/'),
        'DE'    => array('/^(00|\+)?49[0-9]{10}$/', '/^0[0-9]{10}$/'),
        'PT'    => array('/^(00|\+)?351[0-9]{9}$/', '/^[0-9]{9}$/'),
        'PTc'   => array('/^(00|\+)?3519[0-9]{8}$/', '/^9[0-9]{8}$/'),
        'IT'    => array('/^(00|\+)?39[0-9]{10}$/', '/^[0-9]{10}$/'),
        'MO'    => array('/^(00|\+)?377[0-9]{10}$/', '/^[0-9]{10}$/'),
    );

    /**
     *  Retrieve Mondial Relay generic configuration data
     *
     *  @param string $field
     *  @param int | Mage_Core_Model_Store store
     *  @return  mixed
     */
    public function getGenericConfigData($field, $store = true)
    {
        return Mage::getStoreConfig('carriers/mondialrelay/' . $field, $store);
    }
    
    /**
     * Retrieve Mondial Relay shipping method configuration data
     *
     * @param string method
     * @param string field
     * @param int | Mage_Core_Model_Store store
     * @return  mixed
     */
    public function getShippingMethodConfigData($method, $field, $store = true)
    {
        return Mage::getStoreConfig('carriers/' . $method . '/' . $field, $store);
    }

    /**
     * Get allowed Mondial Relay's shipping methods for the given store
     * 
     * @param int | Mage_Core_Model_Store store
     * @return array 
     */
    public function getAvailableMethods($store)
    {
        $_availableMethods = array();
        
        $_carriers = Mage::getStoreConfig('carriers');
        foreach (array_keys($_carriers) as $_carrierCode)
        {
            if (($_carrierCode !== 'mondialrelay') && (false !== strpos($_carrierCode, 'mondialrelay')))
            {
                $_carrier = Mage::getSingleton('Man4x_MondialRelay_Model_Carrier_' . substr($_carrierCode, 12));
                if ($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Abstract)
                {
                    foreach ($_carrier->getAllowedMethods($store) as $_mode => $_data)
                    {
                        $_availableMethods[$_mode] = array(
                            'mode'      => $_carrier->getCarrierCode() . '_' . $_mode,
                            'title'     => $_data['name'],
                            'config'    => $_data,
                        );
                    }
                }
            }
        }
        return $_availableMethods;
    }

    /**
     * Get all Mondial Relay's reverse methods
     * 
     * @return array 
     */
    public function getAllReverseMethods()
    {
        $_allReverseMethods = array();
        
        $_carriers = Mage::getStoreConfig('carriers');
        foreach (array_keys($_carriers) as $_carrierCode)
        {
            if (($_carrierCode !== 'mondialrelay') && (false !== strpos($_carrierCode, 'mondialrelay')))
            {
                $_carrier = Mage::getSingleton('Man4x_MondialRelay_Model_Carrier_' . substr($_carrierCode, 12));
                if ($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Abstract)
                {
                    foreach ($_carrier->getAllReverseMethods() as $_mode => $_data)
                    {
                        if (!isset($_allReverseMethods[$_mode]))
                        {
                            $_allReverseMethods[$_mode] = $_data;
                        }
                    }
                }
            }
        }
        return $_allReverseMethods;
    }
    
    /**
     * Get available Mondial Relay's reverse methods regarding settings
     * 
     * @param int | Mage_Core_Model_Store store
     * @return array 
     */
    public function getAvailableReverseMethods($store = true)
    {
        $_allReverseMethods = $this->getAllReverseMethods();
        $_allowedReverseMethods = explode(',', $this->getGenericConfigData('allowed_reverse_modes', $store));
        $_availableReverseMethods = array();
        
        foreach($_allowedReverseMethods as $_mode)
        {
            if (isset($_allReverseMethods[$_mode]))
            {
                $_availableReverseMethods[$_mode] = $_allReverseMethods[$_mode];
            }
        }
        
        return $_availableReverseMethods;
    }
    
    public function convertWeightInGrams($weight, $store = true, $minimalWeight = 0, $storeIsUnit = false)
    {
        $_unit = $storeIsUnit ? $store : $this->getGenericConfigData('catalog_weight_unit', $store);
        switch (strtolower($_unit))
        {
            case 'kg':
            case 'kilogram':
                $weight *= 1000;
                break;
            case 'oz':
                $weight *= 28.35;
                break;
            case 'lb':
            case 'pound':
                $weight *= 453.6;
        }
        
        $_weight = ceil($weight);
        if ($minimalWeight)
        {
            $_weight = max($_weight, $minimalWeight);
        }
            
        return $_weight;
    }

    /**
     * Get total weight for given shipment items set
     * It seems MondialRelay doesn't evaluate the good weight for bundles
     * (see https://community.magento.com/t5/Small-Business-Merchant-Chat/Configurable-products-amp-weight-issue/td-p/529) 
     * 
     * @param array items
     * @return float 
     */
    public function getItemsWeight($items)
    {
        $_weight = 0;
        
        foreach ($items as $_item)
        {
            $_orderItem = $_item->getOrderItem();
            // Skip if this item is virtual or has children (weight will be calculated for them)
            if ($_orderItem->getIsVirtual() || ($_orderItem->getProduct()->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)) {
                continue;
            }           
            $_weight += $_item->getWeight() * $_item->getQty();
        }
        
        return $_weight;        
    }
    
    /**
     * Explode $str in $nb lines after removing comments
     * Windows (CR+LF)/ Unix (LF) / Apple (CR) Hack for managing CR+LF
     * 
     * @param string str
     * @param int nb
     * @return array
     */
    public function splitLines($str, $nb = 0, $default = '')
    {
        $_lines = array();
        $str = trim($str);
        if ($str)
        {
            // MacOS -> all CR replaced with LF
            if (false === strpos("\n", $str))
            {
                str_replace("\r", "\n", $str);
            } 
            str_replace("\r", '', $str); // Remove all CR                
            preg_replace('#/\*[^*]*\*+([^/][^*]*\*+)*/#', '', $str); // Remove comments
            $_lines = explode("\n", $str);
            if ($nb)
            {
                $nb -= count($_lines);
                while ($nb-- > 0)
                {
                    $_lines[] = $default;
                }
            }
        }
        return $_lines;
    }


    /**
     * Explode $str in lines keeping comments
     * Windows (CR+LF)/ Unix (LF) / Apple (CR) Hack for managing CR+LF
     * 
     * @param string str
     * @return array
     */
    public function splitLinesWithComment($str)
    {
        $_resultLines = array();
        
        // MacOS -> all CR replaced with LF
        if (false === strpos("\n", $str))
        {
            str_replace("\r", "\n", $str);
        }
        // Remove all CR and force comments to be at the beginning of a line
        str_replace(array("\r", "//"), array('', "\n//"), $str);
            
        $_sourceLines = explode("\n", $str);
        foreach ($_sourceLines as $_sourceLine)
        {
            $_line = trim($_sourceLine);
            if (!empty($_line))
            {
                $_resultLines[]= $_line; 
            }
        }
        
        return $_resultLines;
    }

    /**
     * Remove accent, uppercase and truncate
     * cf http://www.weirdog.com/blog/php/supprimer-les-accents-des-caracteres-accentues.html
     * @TODO manage charset formats
     * 
     * @param string str
     * @param int len
     * @param string charset
     * @return string
     */
    public function removeAccent($str, $len = 32, $charset = 'utf-8')
    {
        // Truncate
        $str = substr($str, 0, $len);
        // Replace special characters with htmlentities
        $str = trim(htmlentities($str, ENT_NOQUOTES, $charset));
        // Replace htmlentities
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // e.g. '&oelig;'
        // Remove other characters
        $str = preg_replace('#&[^;]+;#', '', $str);

        return strtoupper($str);
    }


    /**
     * Sort pick-up by nearness
     * 
     * @param array a
     * @param array b
     * @return -1 | 0 | 1 
     */
    public function sortByNearness($a, $b)
    {
        if ($a['distance'] == $b['distance'])
        {
            return 0;
        }
        return ($a['distance'] < $b['distance']) ? -1 : 1;
    }

    /**
     * Format phone number according to Mondial Relay ws
     * If number is invalid regarding country regex, return alternative number (if set) or the number itself
     * 
     * @param string phone
     * @param string country
     * @param bool cellular (opt)
     * @param string altphone (opt)
     * @return string 
     */
    public function formatPhone($phone, $country, $cellular = false, $altphone = false)
    {
        // We build regexs to match the number
        $_regexs = $cellular && isset($this->_phoneRegex[$country . 'c']) ? $this->_phoneRegex[$country . 'c'] : array();
        $_regexs = array_merge($_regexs, isset($this->_phoneRegex[$country]) ?
                $this->_phoneRegex[$country] : $this->_phoneRegex['DEF']);
        
        // We remove all chars except digits
        $_phone = preg_replace("/[^0-9]/", "", $phone);
        
        $_nullphone = str_repeat('0', strlen($_phone));

        // If leading numbers are matching country prefix, we add a leading 00 else we add 00 + country prefix (except for french
        // numbers or if number already starts with 00)
        if (($_phone !== $_nullphone) && isset($this->_phoneIntPrefix[$country]))
        {
            if ($this->_phoneIntPrefix[$country] === substr($_phone, 0, strlen($this->_phoneIntPrefix[$country])))
            {
                $_phone = '00' . $_phone;
            }
            else if (('FR' !== $country) && ('00' !== substr($_phone, 0, 2)))
            {
                $_phone = '00' . $this->_phoneIntPrefix[$country] . $_phone;
            }
        }
        
        $_match = false;
        while (!$_match && count($_regexs))
        {
            $_match = preg_match(array_shift($_regexs), $_phone);
        }

        // We also check that it's not an 0-number
        $_match = $_match && ($_nullphone !== $_phone);
        
        $_result = $_match ? $_phone : ($altphone ? $altphone : $_phone);

        return $_result;
    }
    
    /**
     * Determines if given store (or current) is admin or not
     * Return false if error 
     * 
     * @param int | Mage_Core_Model_Store store (opt)
     * @return bool
     */
    public function isAdmin($store = true)
    {
        $_store = Mage::app()->getStore($store);
        $_isAdmin = $_store->isAdmin() || Mage::getDesign()->getArea() === 'adminhtml';
        return $_isAdmin;
    }

    /**
     * Determines if given store (or current) is in debug mode or not
     * Return false if error 
     * 
     * @param int | Mage_Core_Model_Store store (opt)
     * @return bool
     */
    public function isDebugMode($store = null)
    {
        return (bool) Mage::getStoreConfig('carriers/mondialrelay/debug_mode', $store);
    }
}

