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
 * @desc        Generic controller
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse man4x[@]hotmail[.fr]
 */

class Man4x_MondialRelay_IndexController
    extends Mage_Core_Controller_Front_Action
{
    
    /**
     * Query pickup(s)
     * Called from
     *  - JS Man4xMondialRelayPickupSelectionClass.query() in pickupselectionform.phtml
     * 
     * @return string (if error) | array (in JSON format)
     */
    public function gatherpickupsAction()
    {
        $_countryId = $this->getRequest()->getPost('country');
        $_city = trim($this->getRequest()->getPost('city'));
        $_postcode = trim($this->getRequest()->getPost('postcode'));
        $_weight = trim($this->getRequest()->getPost('weight'));
        $_pickupId = trim($this->getRequest()->getPost('pickup'));
        
        $_result = array('data' => null);
        
        if ($_pickupId || !empty($_postcode))
        {
            // Pickup id or postcode supplied: we search for pickup(s)
            $_result = $this->_pickupList($_postcode, $_countryId, $_weight, $_pickupId);
        }
        
        // If no pickups found and a city is provided, we search for matching cities
        if (!is_array($_result['data']) && !empty($_city))
        {
            $_result = $this->_cityList($_city, $_postcode, $_countryId);
            
            if (is_array($_result['data']) && (count($_result['data']) === 1))
            {
                // A single city matching: we rerun pickup research with its postcode 
                $_result = $this->_pickupList($_result['data'][0]['postcode'], $_countryId, $_weight);
            }
        }
		
		// If "application/json" type not allowed by the server, uncomment:
		// echo(Zend_Json::encode($_result)); die(); 

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Zend_Json::encode($_result));
    }

    /**
     * Handler for pickup selection
     * Save the selected pickup details in checkout session for later use (shipment save)
     */ 
    public function savepickupinsessionAction()
    {
        $_pickup = array();
        foreach ($this->getRequest()->getPost() as $_key => $_value)
        {
            $_pickup[$_key] = $_value;
        }
        Mage::getSingleton('checkout/session')->setData('selected_pickup', $_pickup);
    }
    
    /**
     * Build the city list for display
     * 
     * @param string city
     * @param string postcode
     * @param string countryId
     * @return array 
     */
    private function _cityList($city, $postcode, $countryId)
    {        
        $_result = array(
            'type'  => 'error',
            'title' => $this->__('City List'),
        );
        
        try
        {
            $_store = $this->_getInvolvedStore();
            $_wsResult = Mage::helper('mondialrelay/ws')->wsGetCities($city, $postcode, $countryId, $_store);
        
            $_result['data'] = $_wsResult;
        
            if (is_array($_wsResult))
            {
                $_result['type'] = 'city-list';
                if (! count($_wsResult))
                {                
                    $_result['data'] = $this->__('No matching results.');
                }
            }
        }
        catch (Exception $_e)
        {
            // We return actual / generic error message depending on debug mode
            $_result['data'] = Mage::helper('mondialrelay')->isDebugMode($_store) ?
                    $_e->getMessage() : $this->__('Mondial Relay service temporary unavailable.');
        }
        
        return $_result;
    }

    /**
     * Build the pickup(s) list for display
     * 
     * @param string postcode
     * @param string countryId
     * @param string weight
     * @param string pickupId (opt)
     * @return array 
     */
    private function _pickupList($postcode, $countryId, $weight, $pickupId = '')
    {
        $_result = array(
            'type'  => 'error',
            'title' => $this->__('Pick-up List'),
        );
                        
        try
        {
            $_store = $this->_getInvolvedStore();
            $_wsResult = Mage::helper('mondialrelay/ws')->wsGetPickups($postcode, $countryId, $weight, $_store, $pickupId);
        
            $_result['data'] = $_wsResult;
        
            if (is_array($_wsResult))
            {
                $_result['type'] = 'pickup-list';
                if (!count($_wsResult))
                {
                    $_result['data'] = $this->__('No matching results.');
                }
            }
        }
        catch (Exception $_e)
        {            
            // We return actual / generic error message depending on debug mode
            $_result['data'] = Mage::helper('mondialrelay')->isDebugMode($_store) ?
                    $_e->getMessage() : $this->__('Mondial Relay service temporary unavailable.');
        }
        
        return $_result;
    }

    /**
     * Get involved store for current action
     * 
     * @return int 
     */
    private function _getInvolvedStore()
    {
        // Case frontend
        $_store = Mage::app()->getStore();
        
        if (Mage::helper('mondialrelay')->isAdmin() && ($_shipment = Mage::registry('current_shipment')))
        {
            $_store = $_shipment->getStoreId(); 
        }
        
        return $_store;
    }
}