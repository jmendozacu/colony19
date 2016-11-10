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
 * @description Helper for web services        
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse man4x[@]hotmail[.fr]
 */

class Man4x_MondialRelay_Helper_Ws
    extends Man4x_MondialRelay_Helper_Data
{   
    const BASE_URL = 'http://www.mondialrelay.fr';
    const LOG_FILE = 'man4x_mondialrelay_debug.log';

    const MAX_CITY_LIST = 15;
    const CITY_MIN_CHAR = 3;
    const CITY_MAX_CHAR = 26;

    /*
     * Average delay before shipment (in days)
     * For 'DelaiEnvoi' param of WSI3_PointRelais_Recherche
     */
    const DELAY_BEFORE_SHIPMENT = 0;
    
    // Available languages for Mondial Relay backend
    protected $_availableLanguages = array('FR', 'NL', 'ES');
    protected $_defaultLanguage = 'FR';
    
    /**
     * Retrieve 'Enseigne' web service params from module settings
     * 
     * @param int store
     * @return string
     */   
    public function getEnseigne($store = true)
    {
        // We get company_ref_tracking parameter and remove two last characters
        $_crt = $this->getGenericConfigData('company_ref_tracking', $store);
        $_enseigne = substr($_crt, 0, strlen($_crt) - 2);
        return $_enseigne;
    }
    
    /**
     * Get best language code for the given context. Default = french
     * 
     * @param int store (opt)
     * @return string
     */   
    public function getLanguage($store = false)
    {
        $_currentLocale = $store ?
                Mage::getStoreConfig('general/locale/code', $store) :
                Mage::app()->getLocale()->getLocaleCode();
        $_currentLanguage = strtoupper(substr($_currentLocale, 0, 2));
        if (in_array($_currentLanguage, $this->_availableLanguages))
        {
            return $_currentLanguage;           
        }
        return $this->_defaultLanguage;
    }

    /**
     * Call web service
     * !!! May raise exceptions that must be handled in calling method !!!
     * 
     * @param string method (web service to call)
     * @param array params
     * @param string result (data name)
     * @param int store (opt)
     * @return string (error) | object (result)
     */   
    private function _callWs($method, $params, $result, $store = true)
    {
        // Adds security code to params
        $_code = implode('', $params);
        $_code .= $this->getGenericConfigData('key_ws', $store);
        $params['Security'] = strtoupper(md5($_code));
        
        try
        {
            // Web Service Connection
            $_client = new SoapClient($this->getGenericConfigData('url_ws'));
            $_ws = $_client->$method($params);
            $_wsResult = $_ws->$result;

            // Success call: we return object
            if ('0' === $_wsResult->STAT)
            {           
                return $_wsResult;
            }
        }
        catch (SoapFault $_e)
        {
            Mage::throwException('Soap Error - ' . $_e->getMessage());
        }
        catch (Exception $_e)
        {
            Mage::throwException('Web service error - ' . $_e->getMessage());
        }
                
        // Throw module exception
        $_dump = array_merge($params, (array)$_wsResult);
        throw new Man4x_MondialRelay_Exception($method, $_wsResult->STAT, $_dump);
    }
    
    /**
     * Register shipment at Mondial Relay web service
     * Called from:
     *  - Man4x_MondialRelay_Model_Carrier_Abstract->requestToShipment()
     *  - Man4x_MondialRelay_Model_Carrier_Abstract->returnOfShipment()
     * 
     * @param array param
     * @param int store
     * @return array
     * 
     * @desc http://www.mondialrelay.fr/webservice/Web_Services.asmx?op=WSI2_CreationExpedition 
     */
    public function wsRegisterShipment($params, $store)
    {
        //die(var_dump($params) . ' storeId = ' . $store); // test purpose
        
        $_result = $this->_callWs('WSI2_CreationExpedition', $params, 'WSI2_CreationExpeditionResult', $store);
        
        if (property_exists($_result, 'ExpeditionNum'))
        {
            return array('number' => $_result->ExpeditionNum);
        }
        
        $_dump = array_merge($params, (array)$_result);
        throw new Man4x_MondialRelay_Exception('WSI2_CreationExpeditionResult', 'ExpeditionNum', $_dump);
    }
    
    /**
     * Get label URL for the given tracking numbers
     * Called from
     *  - Man4x_MondialRelay_Sales_ShippingController->massLabelPrintingAction()
     *  - Man4x_MondialRelay_Sales_ShippingController->_sendReturnLabelLinkbyEmail()
     *  - Man4x_MondialRelay_Block_Adminhtml_Shipment_View->getShippingLabelUrl()
     * 
     * @param string | array trackings
     * @param int store
     * @return string (labels url or error code)
     * 
     * @desc http://www.mondialrelay.fr/webservice/Web_Services.asmx?op=WSI2_GetEtiquettes 
     */
    public function getWsLabelUrl($trackings, $store = true)
    {
        if (is_array($trackings))
        {
            $trackings = implode(';', $trackings);
        }
        
        $_params = array(
            'Enseigne' => $this->getEnseigne($store),
            'Expeditions' => $trackings,
            'Langue' => 'FR',
        );

        // WS call
        $_result = $this->_callWs('WSI2_GetEtiquettes', $_params, 'WSI2_GetEtiquettesResult', true);
        
        if (property_exists($_result, 'URL_PDF_A4'))
        {
            $_labelUrl = self::BASE_URL . $_result->URL_PDF_A4;
            // We replace the A4 label format with the configured label size
            $_labelSize = $this->getGenericConfigData('label_size', $store);
            return str_replace('&format=A4&', '&format=' . $_labelSize . '&', $_labelUrl);
        }
        
        $_dump = array_merge($_params, (array)$_result);
        throw new Man4x_MondialRelay_Exception('WSI2_GetEtiquettesResult', 'URL_PDF_A4', $_dump);
    }

    /**
     * Get the city list for a given city/postcode/country
     * Called from Man4x_MondialRelay_IndexController->_cityList()
     * 
     * @param string city
     * @param string postcode
     * @param string country
     * @param int store
     * @return string | array
     * 
     * @desc: http://www.mondialrelay.fr/webservice/Web_Services.asmx?op=WSI2_RechercheCP
     */
    public function wsGetCities($city, $postcode, $country, $store = true)
    {
        $city = substr($city, 0, self::CITY_MAX_CHAR);
        
        $_params = array(
            'Enseigne'  => $this->getEnseigne($store), // $this->getGenericConfigData('company', $store),
            'Pays'      => $country,
            'Ville'     => '',
            'CP'        => $postcode,
            'NbResult'  => self::MAX_CITY_LIST,
        );
        
        $_str = '';
        do
        {
            $_str .= ($city . ' ');
            $_params['Ville'] = $city;
            $city = substr($city, 0, -1);

            // WS call
            $_result = $this->_callWs('WSI2_RechercheCP', $_params, 'WSI2_RechercheCPResult', $store);
        }
        while ((strlen($city) > self::CITY_MIN_CHAR)
            && (is_string($_result) || (property_exists($_result, 'Liste') && !property_exists($_result->Liste, 'Commune'))));

        if (is_string($_result))
        {
            return $_result;
        }
        
        if (property_exists($_result, 'Liste'))
        {
            $_cities = array();
            if (property_exists($_result->Liste, 'Commune'))
            {
                if (is_array($_result->Liste->Commune))
                {
                    foreach ($_result->Liste->Commune as $_city)
                    {
                        $_cities[] = array(
                            'city'       => $_city->Ville,
                            'postcode'   => $_city->CP,
                            'country_id' => $_city->Pays,
                        );
                    }
                }
                else /* One result only */
                {
                    $_cities[] = array(
                        'city'       => $_result->Liste->Commune->Ville,
                        'postcode'   => $_result->Liste->Commune->CP,
                        'country_id' => $_result->Liste->Commune->Pays,
                    );                  
                }
            }
            return $_cities;
        }
                
        $_dump = array_merge($_params, (array)$_result);
        throw new Man4x_MondialRelay_Exception('WSI2_RechercheCPResult', 'Liste->Commune', $_dump);
    }

    /**
     * Get the pick-ups list for a given postcode/country sorted by growing nearness
     * Called from Man4x_MondialRelay_IndexController->_pickupList
     * 
     * @param string postcode
     * @param string country
     * @param int weight (in catalog unit)
     * @param int store
     * @param int pickupId
     * @return string | array
     * 
     * @desc: http://www.mondialrelay.fr/webservice/Web_Services.asmx?op=WSI3_PointRelais_Recherche
     */
    public function wsGetPickups($postcode, $country, $weight, $store, $pickupId = '')
    {
        // We convert package weight in grams
        $_weightInGrams = $this->convertWeightInGrams($weight, $store, Man4x_MondialRelay_Model_Carrier_Abstract::MIN_WEIGHT);
        
        // We get action mode for the given weight (DRI or empty)
        $_actionMode = Man4x_MondialRelay_Model_Carrier_Pickup::getActionMode((int)$_weightInGrams);
        
        // Params for the WS (keep in  strict order)
        $_params = array(
            'Enseigne'          => $this->getEnseigne($store), // self::getGenericConfigData('company', $store),
            'Pays'              => $country,
            'NumPointRelais'    => $pickupId,
            'CP'                => $postcode,
            'Poids'             => '' . $_weightInGrams,
            'Action'            => $_actionMode,
            'DelaiEnvoi'        => self::DELAY_BEFORE_SHIPMENT,
        );
        //die(var_dump($_params));

        // WS call
        $_result = $this->_callWs('WSI3_PointRelais_Recherche', $_params, 'WSI3_PointRelais_RechercheResult',
                $store, $this->isAdmin($store));
        $_pickups = array();
        
        if (is_string($_result))
        {
            return $_result;
        }
        
        if (property_exists($_result, 'PointsRelais'))
        {           
            if (property_exists($_result->PointsRelais, 'PointRelais_Details'))
            {
                $_ajaxResult = is_array($_result->PointsRelais->PointRelais_Details) ?
                        $_result->PointsRelais->PointRelais_Details :
                        array($_result->PointsRelais->PointRelais_Details);
                
                // We sort the pickup in growing order of nearness
                // @TODO ask MR is pickups are already sorted this way
                foreach ($_ajaxResult as $_pickup)
                {
                    $_pickups[] = $this->_extractPickupData($_pickup);              
                }        
                usort($_pickups, array(Mage::helper('mondialrelay'), 'sortByNearness'));
        
                // We remove extra pick-ups
                $_relayCount = (int) Mage::getStoreConfig('carriers/mondialrelaypickup/relay_count', $store);
                while (count($_pickups) > $_relayCount)
                {
                    array_pop($_pickups);
                }               
            }
            return $_pickups;
        }
        
        $_dump = array_merge($_params, (array)$_result);
        throw new Man4x_MondialRelay_Exception('WSI3_PointRelais_RechercheResul', 'PointsRelais', $_dump);
    }
    
    /**
     * Extract and format pickup data returned by WSI3_PointRelais_Recherche
     *
     * @param object $pickup
     * @return array
     */
    protected function _extractPickupData($pickup)
    {
        $_local = '';
        if (property_exists($pickup, 'Localisation1'))
        {
            $_local = $pickup->Localisation1;
            if (property_exists($pickup, 'Localisation2'))
            {
                $_local .= ' ' . $pickup->Localisation2;
            }
        }
        
        $_pickup = array(
            'id'            => $pickup->Num,
            'name'          => trim($pickup->LgAdr1) . (trim($pickup->LgAdr2) ? ' ' . trim($pickup->LgAdr2) : ''),
            'street'        => trim($pickup->LgAdr3) . (trim($pickup->LgAdr4) ? ' ' . trim($pickup->LgAdr4) : ''),
            'postcode'      => $pickup->CP,
            'city'          => $pickup->Ville,
            'latitude'      => (float) str_replace(',', '.', $pickup->Latitude),
            'longitude'     => (float) str_replace(',', '.', $pickup->Longitude),
            'local'         => $_local,
            'country'       => $pickup->Pays,
            'distance'      => (float) $pickup->Distance,
            'information'   => $pickup->Information,
            'horaires'      => $this->_extractOpeningHours($pickup),
            'holiday'       => $pickup->Informations_Dispo,
            'image'         => $pickup->URL_Photo,
        );
        
        return $_pickup;       
    }
    
    /**
     * Extract and format opening hours for the pickup returned by wsGetPickups
     *
     * @param object $pickup
     * @return array
     */
    protected function _extractOpeningHours($pickup)
    {
        $_week = array(
            $this->__('Monday')      => $pickup->Horaires_Lundi,
            $this->__('Tuesday')     => $pickup->Horaires_Mardi,
            $this->__('Wednesday')   => $pickup->Horaires_Mercredi,
            $this->__('Thursday')    => $pickup->Horaires_Jeudi,
            $this->__('Friday')      => $pickup->Horaires_Vendredi,
            $this->__('Saturday')    => $pickup->Horaires_Samedi,
            $this->__('Sunday')      => $pickup->Horaires_Dimanche,
        );
        
        // Formating working hours
        $_closedStr = $this->__('Closed');
        $_hStr = $this->__('%sh%s');
        
        foreach ($_week as $_day => $_hours)
        {
            $_hour = $_hours->string;
            if (! is_array($_hour) || count($_hour) != 4)
            {
                continue;
            }
            $_openingStr = '';
            $_open = true;
            
            for ($_o = 0; $_o < 4; $_o++)
            {
                if ('0000' != $_hour[$_o])
                {
                    $_openingStr .= sprintf($_hStr, substr($_hour[$_o], 0, 2), substr($_hour[$_o], 2, 2));
                    $_openingStr .= ($_open ? '-' : ' ');
                    $_open = ! $_open;
                }
                else
                {
                    $_o++;
                }               
            }
            
            $_week[$_day] = ('' === $_openingStr) ? $_closedStr : $_openingStr;
        }
            
        return $_week;
    }
    
    
}

