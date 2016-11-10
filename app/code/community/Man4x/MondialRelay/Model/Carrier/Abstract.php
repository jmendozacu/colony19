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
 * @desc        Mondial Relay generic Mondial Relay carrier model class 
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 *
 * Reminder: each Mondial Relay shipping mode has one or more shipping methods  
 * This class is defined as a shipping mode in config.xml to be displayed in the back-end shipping panel for gathering all
 * generic Mondial Relay params but is also made as disabled since it acts as a parent abstract class for actual shipping methods
 * (Home and Pickup)
 */

class Man4x_MondialRelay_Model_Carrier_Abstract
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    /**********
     * CONSTANTS
     ***********/
    
    /*
     * Cache tag for rates 
     */
    const CACHE_TAG = 'man4x';
        
    const CSV_SEPARATOR = ";";    
    const CSV_EOL = "\r\n";
    
    /*
     * Minimal weight (in grams) for Mondial Relay package
     */
    const MIN_WEIGHT = 100;
    
    const BASE_URL = 'http://www.mondialrelay.fr';


    /************
     * PROPERTIES
     ************/

    /*
     * Mage_Shipping_Model_Carrier_Abstract
     */
    protected $_code = 'mondialrelay';

    /**
     *  All defined shipping modes and their config
     *  Result pattern:
     *      mondial relay web service shipping method code => array(
     *          'name'                  => shipping method name (as Mondial Relay commercial doc)
     *          'data_config_suffix'    => suffix to append to get actual config attributes,
     *          'max_weight'            => maximum parcel weight (in g)
     *          'multipack'             => is multipack available ?
     *          'reverse'               => reverse shipping method (if this methods also acts as reverse method)
     *          'reverse_title'         => reverse title (id)
     *          'default_reverse'       => default reverse shipping method and mode (method_mode)
     *          'country_settings'      => allowed countries (each country setting can overide default property value) 
     *      )
     */
    protected $_allMethods = array();
    

    /********
     * GETTER
     ********/
    
    /*
     * Get all modes for a given Mondial Relay method regardless configuration setting
     * 
     * @return array
     */
    public function getAllMethods()
    {
        return $this->_allMethods;       
    }
        
    /*
     * Get all reverse modes for a given Mondial Relay method regardless configuration settings
     * 
     * @return array
     */
    public function getAllReverseMethods()
    {
        $_allReverseMethods = array();
        
        foreach($this->getAllMethods() as $_mode => $_config)
        {
            if (isset($_config['reverse']) && !isset($_allReverseMethods[$_config['reverse']]))
            {
                $_allReverseMethods[$_config['reverse']] = array(
                    'mode'      => $this->_code . '_' . $_mode,
                    'title'     => $_config['reverse_title'],
                    'config'    => $_config,
                );
            }
        }
        
        return $_allReverseMethods;
    }

    /**************************************
     * Mage_Shipping_Model_Carrier_Interface
     **************************************/
    
    /*
     * Get all available methods regarding configuration
     * 
     * @param int | Mage_Core_Model_Store (store (opt)
     * @return array 
     */
    public function getAllowedMethods($store = true)
    {
        $_allowedMethods = array();
        
        if ('mondialrelay' != $this->_code)
        {
            foreach(explode(',', $this->getConfigData('allowed_modes', $store)) as $_mode)
            {
                $_allowedMethods[$_mode] = $this->_allMethods[$_mode];
            }
        }
        
        return $_allowedMethods;       
    }

    public function isTrackingAvailable()
    {
        return true;
    }
    
    
    /***********************************************
     * Mage_Shipping_Model_Carrier_Abstract overrides
     ***********************************************/

    /**
     * Check if method is active and if there is at least one allowed mode for the given method
     *
     * @return bool
     */
    public function isActive()
    {
        $_isActive = parent::isActive() && count(explode(',', $this->getConfigData('allowed_modes'))) > 0;
        return $_isActive;
    }

    /**
     *  Check if shipping method is available for the given request according to available shipping countries in backend
     *  -> called from Mage_Shipping_Model_Shipping->collectCarrierRates()
     * 
     *  @param Mage_Shipping_Model_Rate_Request request
     *  @return Mage_Shipping_Model_Rate_Result | Mage_Shipping_Model_Rate_Result_Error
     */    
    public function checkAvailableShipCountries(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->setStore($request->getStoreId());
        
        $_countries = explode(',', $this->getConfigData('specificcountry'));
        if ($_countries && !in_array($request->getDestCountryId(), $_countries))
        {
            return $this->_createErrorRate('This shipping method is not available for selected delivery country.');
        }
        return $this;     
    }
    
    /**
     * Processing additional validation to filter available shipping methods regarding:
     *      - Mondial Relay's logistics (i.e. weight/country limitations)
     *      - Optional banned items
     *      - Price (i.e. a price is defined for the request)
     * -> called from Mage_Shipping_Model_Shipping->collectCarrierRates (before collectRates)
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Carrier_Abstract | Mage_Shipping_Model_Rate_Result_Error | false
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->setStore($request->getStoreId());
        
        $this->_rates = array();
        
        // Calculate package weight in grams (considering minimal parcel weight)
        $_weightInGrams = $this->_getPackageWeightInGrams($request);
        $_destCountry = $request->getDestCountryId();
        
        // Gathers applicable shipping modes for the given request
        foreach ($this->getAllowedMethods() as $_mode => $_config)
        {
            if (    // Check if Mondial Relay allows this mode for this country
                    !array_key_exists($_destCountry, $_config['country_settings'])
                    // Check if weight is below max weight for the given mode
                    || $_weightInGrams > $this->getMethodConfig($_mode, 'max_weight', $_destCountry)
                    // Check if there's a banned item in the order
                    || $this->_hasBannedItem($request, $_mode)
                    // Check there's a price for the given request
                    || (false === ($_price = $this->_getPrice($request, $_mode)))
            )
            {
                continue;
            }
            
            $this->_rates[$_mode] = array(
                'config' => $_config,
                'price' => $_price,
            );
        }
        
        $_return = empty($this->_rates) ?
                $this->_createErrorRate('This shipping method is not available for this order.') :
                $this;
        
        return $_return;
    }

    /**
     *  Gathers applicable shipping methods for the given request
     *  -> called from Mage_Shipping_Model_Shipping->collectCarrierRates
     * 
     *  @param Mage_Shipping_Model_Rate_Request request
     *  @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->setStore($request->getStoreId());

        // @TODO: Looking $request to know how calculate package value with tax and discount       
        $_wInGrams = $this->_getPackageWeightInGrams($request);
        
        // Adds package weight to method title is set in configuration
        $_weightTxt = $this->getGenericConfigData('display_weight') ?
            ' (' . (($_wInGrams <= self::MIN_WEIGHT) ? Mage::helper('mondialrelay')->__('< ') : '') . $_wInGrams
                . ' ' . Mage::helper('mondialrelay')->__('g') . ')': '';

        $_rates = Mage::getModel('shipping/rate_result');

        // Gathers relevant shipping methods for the given request
        foreach ($this->_rates as $_mode => $_data)
        {
            $_dataSuffix = isset($_data['config']['data_config_suffix']) ? $_data['config']['data_config_suffix'] : '';
            
            $_method = Mage::getModel('shipping/rate_result_method');
            $_method->setCarrier($this->_code);
            $_method->setCarrierTitle($this->getGenericConfigData('title'));
            $_method->setMethod($_mode);
            $_method->setMethodTitle($this->getConfigData('methodtitle' . $_dataSuffix) . $_weightTxt);
            $_method->setMethodDescription($this->getConfigData('desc' . $_dataSuffix));
            $_method->setPrice($_data['price']);

            $_rates->append($_method);
        }

        return $_rates;
    }
    
    /**
     *  Determine whether zip-code is required for the country of destination
     *
     *  @param string|null $countryId
     *  @return bool
     */
    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !Mage::helper('directory')->isZipCodeOptional($countryId);
        }
        return true;
    }

    /**
     *  Check if carrier has shipping label option available
     *  If set to true and mode enables multipack, adds the "Create shipping label" checkbox in the shipment admin form
     *  If this checkbox is checked when "Save shipment" button is click, the packaging popup is displayed to compose packages
     * 
     *  @return bool
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }
    
    /**
     *  Do request to shipment.
     *  Core proccess is:
     *  Shipment form
     *      Submission and flag 'create_shipping_label' is set in the post request (i.e. checkbox checked)
     *      -> packages definition form -> submission
     *          -> Mage_Adminhtml_Sales_Order_ShipmentController->saveAction()
     *              -> Mage_Adminhtml_Sales_Order_ShipmentController->createShippingLabel()
     *                  -> Mage_Shipping_Model_Shipping->requestToShipment()
     *                      -> {shipping_carrier_model}->requestToShipment()       
     *
     *  @param Mage_Shipping_Model_Shipment_Request $request (defined by Mage_Shipping_Model_Shipping->requestToShipment method)
     *  @return Varien_Object
     */
    public function requestToShipment(Mage_Shipping_Model_Shipment_Request $request)
    {
        $this->setStore($request->getStoreId());
        
        // If weight isn't manually set from mass shipping grid, we calculate real shipped weight (in case of partial shipping)
        if (!$this->_getRealWeight($request))
        {
            $_shippedWeight = Mage::helper('mondialrelay')->getItemsWeight($request->getOrderShipment()->getAllItems());
            $request->setPackageWeight($_shippedWeight);
        }
       
        // Collect request-dependant parameters for web service registration and complete packages param
        $_params = $this->_collectShipmentParams($request);
        $_rOS = new Varien_Object();
        
        // Format packages to comply with Mondial Relay web service (create a global package if none is defined)
        // If multipack, check if multipacking is available for this weight/country/shipping method
        $_packages = $this->_formatPackages($request);
        if (count($_packages) > 1 && !$this->getMethodConfig($_params['ModeLiv'], 'multipack', $_params['Dest_Pays']))
        {
            $_error = Mage::helper('mondialrelay')->__(
                                    'Mondial Relay shipment error for order #%s (%s)', 
                                    $request->getOrderShipment()->getOrder()->getIncrementId(),
                                    Mage::helper('mondialrelay')->__('Multipack is unavailable for this method'));
            $_rOS->setErrors($_error);
        }
        else
        {
            $_tracks = array();
            
            // We calculate total weight of packages
            $_packagesWeight = 0;
            foreach($_packages as $_package)
            {
                $_packagesWeight += $_package['weight_in_grams'];
            }
            $_params['NbColis'] = (string) count($_packages);
            $_params['Poids'] = (string) $_packagesWeight;
            
            $_ws = Mage::helper('mondialrelay/ws')->wsRegisterShipment($_params, $this->getStore());
            
            // Shipment success
            // (if it failed, an exception's been raised by wsRegisterShipment and will be handled by shipmentController)
            // We are forced here to set a rump pdf-valid value for label_content not to bypass tracking registration
            // (see ShipmentController->_createShippingLabel)
            $_tracks[]= array(
                'label_content'     => base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw=='),
                'tracking_number'   => $_ws['number']
            );
            
            $_rOS->setInfo($_tracks);
        }
        
        return $_rOS;
    }
   
    /**
     * Request a Return Marchandize Authorization
     *
     * @param $request
     * @return Varien_Object
     * 
     */
    public function returnOfShipment($request)
    {
        $this->setStore($request->getStoreId());
        
        // Collect request-dependant parameters for web service registration
        $_params = $this->_collectReturnParams($request);
        $_ws = Mage::helper('mondialrelay/ws')->wsRegisterShipment($_params, $this->getStore());
   
        // Reverse shipment success
        // if it failed, an exception's been raised by wsRegisterShipment and will be handled by shippingController) 
        $_rOS = new Varien_Object();
        $_rOS->setNumber($_ws['number']);       
        return $_rOS;
    }


    /******************************************
     * Man4x_MondialRelay_Model_Carrier_Abstract
     ******************************************/

    /**
     *  Retrieve Mondial Relay generic configuration data
     *  Warning: these data can be store-leveled
     *
     *  @param string $field
     *  @return  mixed
     */
    public function getGenericConfigData($field)
    {
        return Mage::getStoreConfig('carriers/mondialrelay/' . $field, $this->getStore());
    }

    /**
     *  Retrieve config parameter for a given method / country
     *  Note: country settings can override method-level parameters
     *
     *  @param string mode
     *  @param string param
     *  @param string country (opt)
     *  @return null | mixed
     */
    public function getMethodConfig($mode, $param, $country = false)
    {
        $_value = null;
        
        if (isset($this->_allMethods[$mode]))
        {
            $_value =  isset($this->_allMethods[$mode][$param]) ? $this->_allMethods[$mode][$param] : null;
        
            if ($country)
            {
                $_countrySettings = $this->_allMethods[$mode]['country_settings'];
                $_value = isset($_countrySettings[$country]) && isset($_countrySettings[$country][$param]) ?
                        $_countrySettings[$country][$param] : $_value;
            }
        }
        
        return $_value;
    }

    /*
     * Get allowed reverse modes for a given Mondial Relay method according to settings
     * 
     * @return array
     */
    public function getAllowedReverseMethods()
    {        
        $_allowedReverseMethods = array();
        $_allReverseMethods = $this->getAllReverseMethods();
        
        foreach(explode(',', $this->getGenericConfigData('allowed_reverse_modes')) as $_mode)
        {
            if (isset($_allReverseMethods[$_mode]))
            {
                $_allowedReverseMethods[$_mode] = $_allReverseMethods[$_mode];
            }
        }
        return $_allowedReverseMethods;       
    }
    
    /**
     *  Get default reverse method
     *
     *  @param string mode
     *  @param string country (opt)
     *  @return string
     */
    public function getDefaultReverseMethod($mode, $country = false)
    {
        return $this->getMethodConfig($mode, 'default_reverse', $country);
    }

    /**
     * Get package weight in grams. If not set, we calculate corresponding weight in grams
     *  
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return float
     */
    protected function _getPackageWeightInGrams($request)
    {
        if (!isset($request->_data['package_weight_in_grams']))
        {
            $_weightInGrams = Mage::helper('mondialrelay')->convertWeightInGrams($request->getPackageWeight(),
                    $request->getStoreId(), self::MIN_WEIGHT);
            $request->setData('package_weight_in_grams', $_weightInGrams);
            
        }
        return $request->getPackageWeightInGrams();
    }    

    /**
     * Create error rate if 'show_method' is set
     * If a generic error message is set in configuration, we use it otherwise we use the contextual error message
     * 
     * @param string $errMsg
     * @return false | Mage_Shipping_Model_Rate_Result_Error
     */
    protected function _createErrorRate($errMsg)
    {
        $_error = false;
        
        if ($this->getConfigData('showmethod'))
        {
            $_error = Mage::getModel('shipping/rate_result_error');
            $_error->setCarrier($this->_code);
            $_error->setCarrierTitle($this->getGenericConfigData('title'));
            $_cfgErrMsg = $this->getConfigData('specificerrmsg');
            $_error->setErrorMessage($_cfgErrMsg ? $_cfgErrMsg : Mage::helper('mondialrelay')->__($errMsg));          
        }
        
        return $_error;
    }

    /**
     * Determines whether shipment includes a 'banned item' (as set in config)
     * @TODO: enable to restrict mondial relay shippings considering others factors (attribute, category...)
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param string mode
     * @return boolean
     */
    private function _hasBannedItem($request, $mode)
    {
        $_dataSuffix = isset($this->allMethods[$mode]['data_config_suffix']) ?
                $this->allMethods[$mode]['data_config_suffix'] : '';
        
        $_xitems = explode(',', $this->getConfigData('xitems' . $_dataSuffix));
        if (!empty($_xitems))
        {
            foreach ($request->getAllItems() as $_item)
            {
                if (in_array($_item->getProductId(), $_xitems))
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the Mondial Relay rate relevant for the given method / country / region / postcode / condition value / franco
     * If no rate is defined for this request, return false
     * 
     * Note: a closure might have been nicer here to match rate but php 5.3+ needed
     * 
     * @param Mage_Shipping_Model_Rate_Request request request
     * @param string mode (can be used in children classes)
     * @return bool | float
     */
    protected function _getPrice($request, $mode)
    {                
        // Get the condition value regarding request and configuration
        $_condValue = $this->_getConditionValue($request);
        
        // Get table rate
        $_ratesSchedule = $this->_getRatesSchedule();

        $_country = isset($request->_data['dest_country_id']) ? array($request->getDestCountryId()) : array();
        array_push($_country, '*');

        $_region = isset($request->_data['dest_region_id']) ? array($request->getDestRegionId()) : array();
        array_push($_region, '*');

        // Postcode is an user-input data: so uppercase and remove all non alphanumerical character
        $_postcode = isset($request->_data['dest_postcode']) ?
                array(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($request->getDestPostcode())))) : array();
        array_push($_postcode, '*');

        $_price = false;

        // Looking for a matching price in decreasing order of specificity
        // i.e. {c,r,p} -> {c,r,*} -> {c,*,p} -> {c,*,*} -> {*,r,p} -> {*,*,p} -> {*,*,*}
        foreach ($_country as $_c)
        {
            foreach ($_region as $_r)
            {
                if (isset($_ratesSchedule[$_c][$_r]))
                {
                    foreach ($_postcode as $_p)
                    {
                        foreach ($_ratesSchedule[$_c][$_r] as $_pc => $_conds)
                        {
                            // Looking for conditions associated with the first matching post code
                            if (0 === strpos($_p, (string) $_pc))
                            {
                                foreach ($_conds as $_cond)
                                {
                                    // Searching for first rate matching condition
                                    if ($_cond[1] === '*' || $_condValue <= (float)$_cond[1])
                                    {
                                        $_price = $_cond[0];
                                        break 5;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (false !== $_price)
        {
            // Determine if free shipment applies from cart rule
            if ($request->getFreeShipping())
            {
                $_price = 0;
            }            
            else
            {
                $_franco = (float)$this->getConfigData('franco');
                $_cartValue = isset($request->_data['base_subtotal_incl_tax']) ?
                                    (float)$request->_data['base_subtotal_incl_tax']
                                    - (float)$request->_data['package_value']
                                    + (float)$request->_data['package_value_with_discount'] :
                                    (float)$request->_data['package_value_with_discount'];
                
                // Determine if franco is reached
                if ($_franco && $_cartValue >= $_franco)
                {
                    $_price = 0;
                }
            }
        }

        return $_price;
    }

    /**
     * Get Mondial Relay rates from cache/config
     * In config, each rate line is stored as
     *      countryId; regionId; postcode; conditions
     * ...as formated by the Man4x_MondialRelay_Model_System_Config_Validation_Tablerate
     * Save table rates in cache.
     * 
     * @return array
     */
    protected function _getRatesSchedule()
    {
        //Cache loading attempt
        $_cache = Mage::app()->getCache();
        $_cacheKey = self::CACHE_TAG . '_' . $this->_code . '_store_' . $this->getStore();

        if (!is_array($_ratesSchedule = unserialize($_cache->load($_cacheKey))))
        {
            // No cache -> rates extraction from config
            $_ratesSchedule = array();

            $_lines = Mage::helper('mondialrelay')->splitLines($this->getConfigData('table_rate'));
            foreach ($_lines as $_line)
            {
                // Shunt comment lines
                if ('//' === substr($_line, 0, 2))
                {
                    continue;
                }

                $_v = explode(";", $_line);
                $_c = $_v[0]; // country id
                $_r = $_v[1]; // region id
                $_p = (string) $_v[2]; // post code

                if (!isset($_ratesSchedule[$_c]))
                {
                    $_ratesSchedule[$_c] = array();
                }
                if (!isset($_ratesSchedule[$_c][$_r]))
                {
                    $_ratesSchedule[$_c][$_r] = array();
                }

                $_rates = array();
                foreach (explode(',', $_v[3]) as $_cond)
                {
                    $_cds = explode('<', $_cond);
                    $_rates[] = array($_cds[0], $_cds[1]);
                }
                $_ratesSchedule[$_c][$_r][$_p] = $_rates;
            }

            // Save table rates in cache
            $_cache->save(serialize($_ratesSchedule), $_cacheKey, array(Mage_Core_Model_Config::CACHE_TAG));
        }
        return $_ratesSchedule;
    }

    /**
     *  Get condition value for the given request, i.e.
     *      for rate vs weight -> get package weight
     *      for rate vs value -> get package value
     *      for rate vs qty -> get qty of items
     * 
     *  @param Mage_Shipping_Model_Rate_Request request
     *  @return float
     */
    protected function _getConditionValue(Mage_Shipping_Model_Rate_Request $request)
    {
        // Set the condition value to calculate shipping rate depending on config 
        $_condName = $this->getGenericConfigData('rate_condition');
        $_condValue = 0;
        
        switch ($_condName)
        {
            case 'package_weight':
                // Rate vs weight
                $_condValue = $request->getPackageWeight();
                break;

            case 'package_value':
				// Rate vs package value
                if (Mage::getSingleton('checkout/session')->getQuote())
				{
                	$_totals = Mage::getSingleton('checkout/session')->getQuote()->getTotals();
                    $_condValue = $_totals['subtotal']->getValue();
                }
                else
				{
                     $_condValue = $request->getData('package_value_with_discount');
                }
                break;

            case 'package_qty':
                // Rate vs package quantity
                $_condValue = $request->getPackageQty();
                break;
        }
        
        return (float) $_condValue;
    }

    /**
     *  Collect params for MR Web Service WSI2_CreationExpedition from request
     *  !!! Keep parameters in strict order !!!
     *  Inherited classes add/edit fields according to their specificity
     * 
     *  @param Mage_Shipping_Model_Shipment_Request request
     *  @return array
     */
    protected function _collectShipmentParams(Mage_Shipping_Model_Shipment_Request $request)
    {
        $this->setStore($request->getStoreId());
        
        // Shipping Method reduced as method by Mage_Shipping_Model_Shipping->requestToShipment (e.g. 24R_00000)
        $_shippingMethod = explode('_', $request->getShippingMethod());
        
        // Gather Data for web service request
        $_helper = Mage::helper('mondialrelay/ws');
        $_shipperCountry = $request->getShipperAddressCountryCode();
        $_merchantPhone = $_helper->formatPhone($request->getShipperContactPhoneNumber(), $_shipperCountry);
        $_merchantCellPhone = $_helper->formatPhone($request->getShipperContactPhoneNumber(), $_shipperCountry, true, $_merchantPhone);

        $_params = array(
            'Enseigne'      => $_helper->getEnseigne($this->getStore()),
            'ModeCol'       => 'CCC',
            'ModeLiv'       => $_shippingMethod[0], // 24R, LCD...
            'NDossier'      => $request->getOrderShipment()->getOrder()->getIncrementId(),
            'NClient'       => $_helper->removeAccent(preg_replace("/[^A-Za-z]/", "", $request->getRecipientContactPersonLastName()), 8),
            'Expe_Langage'  => $_helper->getLanguage(),
            // Sender address
            'Expe_Ad1'      => $_helper->removeAccent($request->getShipperContactCompanyName()), 
            'Expe_Ad2'      => '',
            'Expe_Ad3'      => $_helper->removeAccent($request->getShipperAddressStreet1()),
            'Expe_Ad4'      => $_helper->removeAccent($request->getShipperAddressStreet2() . ' ' . $request->getShipperAddressStateOrProvinceCode()),
            'Expe_Ville'    => $_helper->removeAccent($request->getShipperAddressCity(), 26),
            'Expe_CP'       => $request->getShipperAddressPostalCode(),
            'Expe_Pays'     => $_shipperCountry,
            'Expe_Tel1'     => $_merchantPhone,
            'Expe_Tel2'     => $_merchantCellPhone,
            'Expe_Mail'     => Mage::getStoreConfig('trans_email/ident_sales/email', $this->getStore()) ?
                                    Mage::getStoreConfig('trans_email/ident_sales/email', $this->getStore()) :
                                    $request->getShipperEmail(),
            // 'Dest_Langage' => strtoupper(substr(Mage::getStoreConfig('general/locale/code', $order->getStore()->getId()), 0, 2)), // Order language
            'Dest_Langage'  => $_helper->getLanguage($this->getStore()),
            'Dest_Ad1'      => $_helper->removeAccent($request->getRecipientContactPersonName()),
            'Dest_Ad2'      => $_helper->removeAccent($request->getRecipientContactCompanyName()),
            'Dest_Ad3'      => $_helper->removeAccent($request->getRecipientAddressStreet1()),
            'Dest_Ad4'      => $_helper->removeAccent($request->getRecipientAddressStreet2() . ' ' . $request->getRecipientAddressRegionCode()),
            'Dest_Ville'    => $_helper->removeAccent($request->getRecipientAddressCity(), 26),
            'Dest_CP'       => $request->getRecipientAddressPostalCode(),
            'Dest_Pays'     => $request->getRecipientAddressCountryCode(),
            'Dest_Tel1'     => $_helper->formatPhone($request->getRecipientContactPhoneNumber(), $request->getRecipientAddressCountryCode(), false),
            'Dest_Tel2'     => $_helper->formatPhone($request->getRecipientContactPhoneNumber(), $request->getRecipientAddressCountryCode(), true),
            'Dest_Mail'     => substr($request->getRecipientEmail(), 0, 70),
            'Poids'         => (string) $this->_getPackageWeightInGrams($request),
            'Longueur'      => '',
            'Taille'        => '',
            'NbColis'       => '', // Set in requestOfShipment
            'CRT_Valeur'    => '0',
            'CRT_Devise'    => 'EUR',
            'Exp_Valeur'    => '0',
            'Exp_Devise'    => 'EUR',
            'COL_Rel_Pays'  => '',
            'COL_Rel'       => '',
            'LIV_Rel_Pays'  => $request->getRecipientAddressCountryCode(),
            'LIV_Rel'       => '0', // Pickup store ID: set by Man4x_MondialRelay_Model_Carrier_Pickup if needed
        );

        return $_params;
    }

    /**
     *  Collect return params for MR Web Service WSI2_CreationExpedition
     *  !!! Keep parameters in strict order !!!
     *  Inherited classes add/edit complement fields according to their requirements
     *  $request is build in Man4x_MondialRelay_Model_Shipping
     * 
     *  @param Mage_Shipping_Model_Shipment_Request request
     *  @return array
     */
    protected function _collectReturnParams(Mage_Shipping_Model_Shipment_Request $request)
    {
        $this->setStore($request->getStoreId());
        
        // Gather Data for web service request
        $_helper = Mage::helper('mondialrelay/ws');
        $_shipperCountry = $request->getShipperCountryCode();
        $_senderPhone = $_helper->formatPhone($request->getShipperContactPhoneNumber(), $_shipperCountry);
        $_senderCellPhone = $_helper->formatPhone($request->getShipperContactPhoneNumber(), $_shipperCountry, true, $_senderPhone);
        $_recipientCountry = $request->getRecipientCountryCode();
        $_recipientPhone = $_helper->formatPhone($request->getRecipientContactPhoneNumber(), $_recipientCountry);
        $_recipientCellPhone = $_helper->formatPhone($request->getRecipientContactPhoneNumber(), $_recipientCountry, true, $_recipientPhone);
              
        $_params = array(
            'Enseigne'      => $_helper->getEnseigne($this->getStore()),
            'ModeCol'       => '', // set by Man4x_MondialRelay_Model_Carrier_xxx
            'ModeLiv'       => 'LCC',
            'NDossier'      => $request->getOrderIncrementId(),
            'NClient'       => $_helper->removeAccent(preg_replace("/[^A-Za-z]/", "", $request->getShipperLastname()), 8),
            'Expe_Langage'  => $_helper->getLanguage($this->getStore()),
            // Sender address
            'Expe_Ad1'      => $_helper->removeAccent($request->getShipperContactPersonName()), 
            'Expe_Ad2'      => $_helper->removeAccent($request->getShipperContactCompanyName()),
            'Expe_Ad3'      => $_helper->removeAccent($request->getShipperStreet1()),
            'Expe_Ad4'      => $_helper->removeAccent($request->getShipperStreet2() . ' ' . $request->getShipperRegion()),
            'Expe_Ville'    => $_helper->removeAccent($request->getShipperCity(), 26),
            'Expe_CP'       => $request->getShipperPostcode(),
            'Expe_Pays'     => $_shipperCountry,
            'Expe_Tel1'     => $_senderPhone,
            'Expe_Tel2'     => $_senderCellPhone,
            'Expe_Mail'     => $request->getShipperEmail(),
            'Dest_Langage'  => $_helper->getLanguage($this->getStore()),
            'Dest_Ad1'      => $_helper->removeAccent($request->getRecipientContactPersonName()),
            'Dest_Ad2'      => $_helper->removeAccent($request->getRecipientCompany()),
            'Dest_Ad3'      => $_helper->removeAccent($request->getRecipientStreet1()),
            'Dest_Ad4'      => $_helper->removeAccent($request->getRecipientStreet2() . ' ' . $request->getRecipientRegionName()),
            'Dest_Ville'    => $_helper->removeAccent($request->getRecipientCity(), 26),
            'Dest_CP'       => $request->getRecipientPostcode(),
            'Dest_Pays'     => $_recipientCountry,
            'Dest_Tel1'     => $_recipientPhone,
            'Dest_Tel2'     => $_recipientCellPhone,
            'Dest_Mail'     => substr($request->getRecipientEmail(), 0, 70),
            'Poids'         => (string) $this->_getPackageWeightInGrams($request),
            'Longueur'      => '',
            'Taille'        => '',
            'NbColis'       => $request->getPackages(),
            'CRT_Valeur'    => '0',
            'CRT_Devise'    => 'EUR',
            'Exp_Valeur'    => '0',
            'Exp_Devise'    => 'EUR',
            'COL_Rel_Pays'  => '', // set by Man4x_MondialRelay_Model_Carrier_Pickup
            'COL_Rel'       => '', // set by Man4x_MondialRelay_Model_Carrier_Pickup
            'LIV_Rel_Pays'  => '',
            'LIV_Rel'       => '', 
        );
        return $_params;
    }

    /**
     *  Recover real weight (if set by administrator from backend before mass shipment) and save in request
     * 
     *  @param Mage_Shipping_Model_Shipment_Request $request
     *  @return bool
     */
    protected function _getRealWeight(Mage_Shipping_Model_Shipment_Request $request)
    {
        $_hasRealWeight = $request->getIsReshipping() || Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight();
        if (!$request->getIsReshipping())
        {
            $_realWeights = Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight();
            $_orderId = $request->getOrderShipment()->getOrder()->getId();

            if ($_hasRealWeight = isset($_realWeights[$_orderId]))
            {
                // We save the real weight in the request
                $request->setPackageWeight($_realWeights[$_orderId]['real_weight']);
            }
        }
        
        return $_hasRealWeight;
    }   

    /**
     * Format shipment packages to comply with Mondial Relay web service
     * Packages can be an array if made through the create packages form (from the new shipment page), an integer if manually
     * defined through return&reshipping form, or can be undefined 
     * 
     * @param Mage_Shipping_Model_Shipment_Request $request
     * @return array
     */
    protected function _formatPackages(Mage_Shipping_Model_Shipment_Request $request)
    {
        /*
         * @TODO
         * Check if weight in $request is the total order weight or only weight of shipped items
         */
        $_packages = $request->getPackages();

        // Packages made through the create packages form
        if (is_array($_packages) && ($_firstPackage = reset($_packages)) && isset($_firstPackage['params']))
        {
            // Get package weight in grams (and sizes in centimeters)
            foreach ($_packages as $_packageId => $_packageData)
            {
                $_packages[$_packageId] = array(
                    'weight_in_grams' => Mage::helper('mondialrelay')->convertWeightInGrams(
                                            $_packageData['params']['weight'],
                                            $_packageData['params']['weight_units'],
                                            self::MIN_WEIGHT,
                                            true)
                );
            }
        }
        // We keep the number of packages (if defined) and set the whole shipment weight on the last one
        else
        {
            $_nbPackages = is_array($_packages) ? count($_packages) : (int)$_packages;
            $_packages = array();
            do 
            {
                $_packages[]= array('weight_in_grams' => (--$_nbPackages > 0) ? 0 : $this->_getPackageWeightInGrams($request));
            }
            while ($_nbPackages > 0);
        }
        
        return $_packages;
    }   
    
    /**
     *  Get params for CSV export
     *  !!! Keep parameters in strict order !!!
     *  Subclasses precise their own specific parameters and build the csv file
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return array
     */
    public function getFlatFileData(Mage_Sales_Model_Order $order)
    {
        $_helper = Mage::helper('mondialrelay/ws');
        $_store = $order->getStore();
        
        // Looking for a real weight defined in admin session
        $_packageWeight = $order->getPackageWeight();
        if (Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight())
        {
            $_realWeights = Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight();

            $_orderId = $order->getId();
            if (isset($_realWeights[$_orderId]))
            {
                $_packageWeight = $_realWeights[$_orderId]['real_weight'];
            }
        }
        $_realWeightInGrams = $_helper->convertWeightInGrams($_packageWeight, $_store, self::MIN_WEIGHT);
        
        $_record = array();
        $_record[] = $_helper->removeAccent(preg_replace("/[^A-Za-z]/", "", $order->getBillingAddress()->getLastname()), 8); // 0.Customer Id
        $_record[] = $order->getIncrementId(); // 1.Order Id
        $_address = $order->getShippingAddress();
        $_record[] = $_helper->removeAccent($_address->getName()); // 2.Customer full name
        $_record[] = $_helper->removeAccent($_address->getCompany()); // 3.Company name
        $_record[] = $_helper->removeAccent($_address->getStreet(1)); // 4.Street line #1
        $_record[] = $_helper->removeAccent($_address->getStreet(2)); // 5.Street line #2
        $_record[] = $_helper->removeAccent($_address->getCity(), 25); // 6.City
        $_record[] = $_address->getPostcode(); // 7.Post code
        $_record[] = $_address->getCountryId(); // 8.Country code
        
        $_shippingPhone = $_address->getTelephone();         
        $_record[] = $_helper->formatPhone( $_shippingPhone ? $_shippingPhone : $order->getBillingAddress()->getTelephone(),
                                            $_shippingPhone ? $_address->getCountryId() : $order->getBillingAddress()->getCountryId(),
                                            false,
                                            $_helper->getGenericConfigData('sender_phone', $_store)); // 9.Home phone number
        $_record[] = ''; // 10.Home phone number
        $_record[] = substr($order->getCustomerEmail(), 0, 70); // 11.Customer email
        $_record[] = ''; // 12.Collection mode (<R>elais, <D>omicile, <A>gence)
        $_record[] = ''; // 13.Pickup ID (mandatory for an "R" collection)
        $_record[] = ''; // 14.Pickup country code (mandatory for an "R" collection)
        
        $_record[] = ''; // 15.Shipping type (<R>elais, <D>omicile) - set by subclass
        $_record[] = ''; // 16.Pickup ID (mandatory for a "24R" shipping mode) - set by pickup subclass 
        
        $_record[] = trim(strtoupper($_address->getCountryId())); // 17.Pickup country code (mandatory for a "24R" shipping mode)
        
        $_method = explode('_', $order->getShippingMethod()); 
        $_record[] = $_method[1]; // 18.Shipping mode (24R...)

        // $_record[] = strtoupper(substr(Mage::getStoreConfig('general/locale/code', $order->getStore()->getId()), 0, 2)); 
        $_record[] = $_helper->getLanguage($_store); // 19.Recipient language code

        $_record[] = '1'; // 20.Parcel boxes number
        $_record[] = (string) $_realWeightInGrams; // 21.Parcel weight (g)
        $_record[] = ''; // 22.Parcel length (cm)
        $_record[] = ''; // 23.Parcel volume (cm3)
        
        $_record[] = round(100 * $order->getBaseTotal()); // 24.Parcel value (cents)
        $_record[] = 'EUR'; // 25.Order currency
        $_record[] = '0'; // 26.Insurrance level
        $_record[] = '0'; // 27.'Montant CRT'
        $_record[] = 'EUR'; // 28.CRT currency
        $_record[] = ''; // 29.Shipping instructions
        $_record[] = '1'; // 30.Notification
        $_record[] = '0'; // 31.Home pickup ('Reprise à domicile)
        $_record[] = '0'; // 32.Setup time ('Temps de montage')
        $_record[] = '0'; // 33.Rendez-vous top

        // 34->43 10 first items
        $_items = $order->getAllItems();
        foreach ($_items as $_item)
        {
            $_record[] = substr($_item->getName(), 0, 30);
            if (count($_record) == 44)
            {
                break;
            }
        }

        return $_record;
    }

    /**
     *  Gathers info for a given tracking
     * 
     *  @param string $trackingNumber
     *  @return Mage_Shipping_Model_Tracking_Result_Status
     */
    public function getTrackingInfo($trackingNumber)
    {
        $_key = md5('<' . $this->getGenericConfigData('company_ref_tracking') . '>' . $trackingNumber
                    . '<' .$this->getGenericConfigData('key_ws') . '>');
        $_trackingUrl = $this->getGenericConfigData('url_tracking')
                            . strtoupper($this->getGenericConfigData('company_ref_tracking'))
                            . '&nexp=' . strtoupper($trackingNumber)
                            . '&crc=' . strtoupper($_key);

        $_trackingStatus = Mage::getModel('shipping/tracking_result_status');
        $_trackingStatus->setCarrier($this->_code)->setPopup(1)->setUrl($_trackingUrl);

        return $_trackingStatus;
    }

    
    /**
     *  Gathers info for a given tracking
     * 
     *  @param string $trackingNumber
     *  @return Mage_Shipping_Model_Tracking_Result
     */
    private function _getTracking($trackingNumber)
    {
    }

}