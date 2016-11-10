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
 * @block       MondialRelay_Block_Adminhtml_Shipment_Return_Form
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * 
 * This block is only output if shipment has a weight (see _toHtml method)
 * 
 * This block is added through layout to handle <adminhtml_sales_order_shipment_view> and is intended to manage return label
 * creation from admin shipment view.
 * It includes a standard form to submit return request and a JS class to manage client-side logic.
 * 
 */

class Man4x_MondialRelay_Block_Adminhtml_Shipment_Return_Form
    extends Mage_Adminhtml_Block_Sales_Order_Shipment_View_Tracking
{
    
    // Reverse methods
    private $_shippingMethods;
    
    // Shipment weight (in catalog unit)
    private $_shipmentWeight = null;
    
    /**
     * Block rendering
     * If shipment weight was 0, we don't output the block since reverse shipment is useless
     * 
     * @return string
     */
    protected function _toHtml()
    {
        $_html = $this->getShipmentWeight() ? parent::_toHtml() : '';
        return $_html;
    }
    
    /**
     * Determines if shipment was made by Mondial Relay
     * 
     * @return bool
     */
    public function isMondialRelayShipment()
    {
        return (false !== strpos($this->getShipment()->getOrder()->getShippingMethod(), 'mondialrelay'));
    }

    /**
     * Get Mondial Relay shipment numbers as array
     * 
     * @return string
     */
    public function getMondialRelayNumbersAsJson()
    {
        $_numbers = array();
        
        $_shipment = $this->getShipment();
        if ($_shipment)
        {
            $_tracks = $_shipment->getAllTracks();
            foreach ($_tracks as $_track)
            {
                if (false !== strpos($_track->getCarrierCode(), 'mondialrelay'))
                {
                    // getTrackNumber() for Magento 1.7+ and $_track->getNumber() for older versions
                    if ($_number = $_track->getTrackNumber() ? $_track->getTrackNumber() : $_track->getNumber())
                    {
                        $_numbers[]= $_number;
                    }
                }
            }
        }
        return json_encode($_numbers);
    }

    /**
     * Get Mondial Relay URL for shipping labels
     * 
     * @return string
     */
    public function getShippingLabelActionUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/mondialrelay_shipping/getShippingLabelUrl',
                array(
                    '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
                ));
    }
    
    /**
     * Get shipment total weight
     * 
     * @return int
     */
    public function getShipmentWeight()
    {
        if (is_null($this->_shipmentWeight))
        {
            $this->_shipmentWeight = Mage::helper('mondialrelay')->getItemsWeight($this->getShipment()->getAllItems());
        }        
        return $this->_shipmentWeight;
    }

    /**
     * Get shipment total weight
     * 
     * @return int
     */
    public function getShipmentWeightInGrams()
    {
       return Mage::helper('mondialrelay')->convertWeightInGrams($this->_shipmentWeight, $this->getShipment()->getStoreId());
    }
    
    /**
     * Get available Mondial Relay shipping methods (reshipping + return) and their config
     * Pattern:
     *      array(
     *          'shipping' => array(
     *              'method_mode' => array(
     *                  'title' => reverse mode title,
     *                  'config' => corresponding MR shipping mode config
     *              ),
     *              ...,
     *          'return' => array(
     *              'method_mode' => array(
     *                  'title' => reverse mode title,
     *                  'config' => corresponding MR shipping mode config
     *              ),
     *              ...,
     *  
     * 
     * @return array
     */
    protected function _getAvailableShippingMethods()
    {        
        if (! $this->_shippingMethods)
        {
            $_storeId = $this->getShipment()->getStoreId();
            $this->_shippingMethods = array(
                'reshipping'    => Mage::helper('mondialrelay')->getAvailableMethods($_storeId),
                'return'        => Mage::helper('mondialrelay')->getAvailableReverseMethods($_storeId),
            );
        }

        return $this->_shippingMethods;
    }

    /**
     * Get shipping methods to be displayed as <optgroup> and <option> in box list
     * 
     * @return array
     */
    public function getAvailableShippingMethodsToOptions()
    {
        $_toOptions = array();
        
        $_shippingMethods = $this->_getAvailableShippingMethods();
        foreach ($_shippingMethods as $_type => $_modes)
        {
            $_toOptions[$_type] = array();
            
            foreach ($_modes as $_mode => $_data)
            {
                $_parts = explode('_', $_data['mode']);
                $_carrier = $_parts[0];
                $_carrierTitle = Mage::helper('mondialrelay')->getShippingMethodConfigData($_carrier, 'methodtitle');
                if (!isset($_toOptions[$_type][$_carrierTitle]))
                {
                    $_toOptions[$_type][$_carrierTitle] = array();
                }
                if (!isset($_toOptions[$_type][$_carrierTitle][$_mode]))
                {
                    $_toOptions[$_type][$_carrierTitle][$_mode] = $_data;
                }
            }
        }

        return $_toOptions;
    }
        
    /**
     * Get available return shipping methods config as JSON
     * 
     * @return string
     */
    public function getShippingMethodsConfigJson()
    {
        $_methods = array();
        
        foreach (array_values($this->_getAvailableShippingMethods()) as $_modes)
        {
            foreach ($_modes as $_mode => $_data)
            {
                if (! isset($_methods[$_mode]))
                {
                    $_methods[$_mode] = array(
                        'mw'    => $_data['config']['max_weight'],
                        'mp'    => $_data['config']['multipack'] ? 1 : 0,
                        'cs'    => $_data['config']['country_settings'],
                    );
                }
            }
        }
        
        return json_encode($_methods);
    }
    
    /**
     * Get default collection pickup query parameters
     * Note: we add id, name, street and country_id because this array will also be used at initialization 
     * 
     * @return false | string
     */
    public function getDefaultQueryParamsJson()
    {
        $_address = $this->getShipment()->getOrder()->getShippingAddress();
        $_params = array(
            'id'            => $this->_getShippedPickupId(),
            'name'          => $_address->getCompany(),
            'street'        => $_address->getStreet(-1),
            'postcode'      => $_address->getPostcode(),
            'city'          => $_address->getCity(),
            'country'       => $_address->getCountryId(),
            'weight'        => $this->getShipmentWeight(),
        );
        
        return json_encode($_params);
    }

    /**
     * Get locale notification messages for reverse form
     * On client-side, JS can replace '###' string with any variable
     * 
     * @return string
     */
    public function getLocaleMessagesJson()
    {
        $_msgs = array(
            'missing_method'        => $this->__('This method is not configured for return shipping'),
            'no_multipack'          => $this->__('Multipack is unavailable for this method'),
            'no_pickup'             => $this->__('You must select a pick-up if you choose this shipping method'),
            'too_heavy'             => $this->__('This method is unavailable for parcels over ### g'),
            'unavailable_country'   => $this->__('This method is unavailable for this country'),
        );
       
        return json_encode($_msgs);
    }

    /**
     * Get weight ratio between catalog unit and gram
     * 
     * @return float
     */
    public function getWeightRatio()
    {
        return Mage::helper('mondialrelay')->convertWeightInGrams(1, $this->getShipment()->getOrder()->getStoreId());
    }
    
    /**
     * Get number of packages for this shipment
     * 
     * @return int
     */
    public function getShipmentPackagesNumber()
    {
        $_packages = $this->getShipment()->getPackages() ? unserialize($this->getShipment()->getPackages()) : array(1);
        return count($_packages);
    }
    
    /**
     * Get customer first name
     * 
     * @return string
     */
    public function getCustomerFirstname()
    {
        return $this->_getCustomerAddress()->getFirstname();
    }
    
    /**
     * Get customer last name
     * 
     * @return string
     */
    public function getCustomerLastname()
    {
        return $this->_getCustomerAddress()->getLastname();
    }

    /**
     *  Get return address
     * 
     *  @param int index
     *  @return string
     */
    public function getCustomerAddress($index)
    {
        $_address = $this->_getCustomerAddress();
        $_line = (1 === $index) ?
                    ($_address->getCompany() ? $_address->getCompany() : $_address->getStreet(1)) :
                    ($_address->getCompany() ? $_address->getStreet(1) : $_address->getStreet(2));
                    
        return $_line;
    }

    /**
     * Get return city
     * 
     * @return string
     */
    public function getCustomerCity()
    {
        return $this->_getCustomerAddress()->getCity();
    }

    /**
     * Get return city
     * 
     * @return string
     */
    public function getCustomerPostcode()
    {
        return $this->_getCustomerAddress()->getPostcode();
    }

    /**
     * Get country select html
     * 
     * @return string
     */
    public function getCountrySelectHtml()
    {
        $_countries = array();
        
        // We collect all available countries for Mondial Relay shipping methods
        foreach (array_values($this->_getAvailableShippingMethods()) as $_modes)
        {
            foreach (array_values($_modes) as $_data)
            {
                foreach (array_keys($_data['config']['country_settings']) as $_country)
                {
                    if (!isset($_countries[$_country]))
                    {
                        $_countries[$_country] = Mage::app()->getLocale()->getCountryTranslation($_country);
                    }
                }
            }
        }
        asort($_countries);
        
        $_current = $this->_getCustomerAddress()->getCountryId();
        $_output = '<select id="mr-country" name="country" class="required-entry select">';
        foreach ($_countries as $_code => $_name)
        {
            $_output .= '<option value="' . $_code . '" ' . ($_code === $_current ? 'selected' : '') . '>' . $_name . '</option>';
        }
        
        return $_output;
    }
    
    /**
     * Get return city
     * 
     * @return string
     */
    public function getCustomerPhoneContact()
    {
        return $this->_getCustomerAddress()->getTelephone();
    }

    /**
     * Get return email contact
     * 
     * @return string
     */
    public function getCustomerEmailContact()
    {
        return $this->_getCustomerAddress()->getEmail();
    }

    /**
     * Get customer address
     * If shipping method was mondialrelaypickup, we use billing address - otherwise 
     * 
     * @return Mage_Sales_Model_Quote_Address
     */
    private function _getCustomerAddress()
    {
        $_order = $this->getShipment()->getOrder();
        $_address = $this->_getShippedPickupId() ? $_order->getBillingAddress() : $_order->getShippingAddress();
        return $_address;
    }
    
    /**
     * Get shipped pickup id (false if shipping method wasn't pickup)
     * 
     * @return false | string
     */
    private function _getShippedPickupId()
    {
        $_id = false;
        
        $_shippingMethod = $this->getShipment()->getOrder()->getShippingMethod();
        if (false !== strpos($_shippingMethod, 'mondialrelaypickup'))
        {
            $_parts = explode('_', $_shippingMethod);
            $_id = $_parts[2];
        }
        
        return $_id;
    }
    
    /**
     * Get action URL for the reverse form
     * 
     * @return string
     */    
    public function getReturnActionUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/mondialrelay_shipping/createReturn',
                array(
                    '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure(),
                    'shipment_id' => $this->getShipment()->getId(),
                ));
    }
    
    /**
     * Get action URL for the reshipping form
     * 
     * @return string
     */    
    public function getReshippingActionUrl()
    {
        return Mage::getModel('adminhtml/url')->getUrl('adminhtml/mondialrelay_shipping/createReshipping',
                array(
                    '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure(),
                    'shipment_id' => $this->getShipment()->getId(),
                ));
    }
}
