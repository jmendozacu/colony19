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
 * @desc        Mondial Relay shipping methods block adapter for back and front orders
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */


class Man4x_MondialRelay_Block_Shippingmethods
    extends Mage_Core_Block_Template
{

    /**
     * Block rendering
     * If no Mondial Relay shipping method is active, we return an empty block
     * 
     * @return string
     */
    protected function _toHtml()
    {
        $_store = $this->_getOrderStore();
        
        if (Mage::getStoreConfigFlag('carriers/mondialrelaypickup/active', $_store) ||
                    Mage::getStoreConfigFlag('carriers/mondialrelayhome/active', $_store))
        {
            return parent::_toHtml();
        }
        return '';
    }

    /*
     * Get methods description for MR shipping methods
     * 
     * @return string (JSON)
     */
    public function getMethodsDescription()
    {
        $_methodsDesc = array();
        
        $_rates = $this->_getShippingRates();        
        foreach ($_rates as $_rate => $_methods)
        {
            foreach ($_methods as $_method)
            {
                if (FALSE !== strpos($_method['code'], 'mondialrelay'))
                {
                    $_methodsDesc[$_method['code']] = $_method['method_description'];
                }
            }
        }
        return json_encode($_methodsDesc);
    }
    
    /*
     * Determines if pickup selection (if available) is on-map or on-list
     * 
     * @return int 
     */
    public function onMapSelection()
    {
        $_onMap = Mage::helper('mondialrelay')->isAdmin() ? 1 :
                Mage::getStoreConfig('carriers/mondialrelaypickup/map_selection', $this->_getOrderStore()) ? 1 : 0;
        return $_onMap;
    }

    /*
     * Determines current active shipping method
     * 
     * @return string
     */
    public function getActiveShippingMethod()
    {
        return $this->_getAddress()->getShippingMethod();
    }

    /*
     * Get  default parameters for first query pickup
     * 
     * @return string (JSON)
     */
    public function getDefaultPickupQueryParameters()
    {
        $_address = $this->_getAddress();
        
        $_params = array(
            'weight'    => $_address->getWeight(),
            'postcode'  => $_address->getPostcode(),
            'city'      => $_address->getCity(),
            'country'   => $_address->getCountryId(),
        );
        
        return json_encode($_params);
    }           
    
    /*
     * Get url for saving pickup id in session (differs whether we are in front or in backend)
     * 
     * @return string
     */
    public function getSavePickupinSessionUrl()
    {
        $_url = Mage::helper('mondialrelay')->isAdmin() ?
                    Mage::getModel('adminhtml/url')->getUrl('adminhtml/mondialrelay_shipping/savePickupInAdminSession',
                        array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure())) :
                    Mage::getUrl('mondialrelay/index/savepickupinsession', 
                        array('_secure' => Mage::app()->getFrontController()->getRequest()->isSecure()));
        
        return $_url;
    }
    
    /*
     * Get current quote (front or backend)
     * 
     * @return Mage_Sales_Model_Quote
     */
    private function _getQuote()
    {
        $_quote = Mage::helper('mondialrelay')->isAdmin() ?
            Mage::getSingleton('adminhtml/session_quote')->getQuote() :
            Mage::getSingleton('checkout/session')->getQuote();
        
        return $_quote;
    }
    
    /*
     * Get store of current quote
     * 
     * @return Mage_Core_Model_Store
     */
    private function _getOrderStore()
    {
        return $this->_getQuote()->getStore();
    }
    
    /*
     * Get address (shipping or billing) of current quote
     * 
     * @return Mage_Sales_Model_Quote_Address
     */
    private function _getAddress()
    {
        $_address = $this->_getQuote()->getShippingAddress();
        if (! $_address)
        {
            $_address = $this->_getQuote()->getBillingAddress();
        }
                
        return $_address;
    }
        
    /*
     * Get shipping rates for current quote
     * 
     * @return array
     */
    private function _getShippingRates()
    {
        return $this->_getAddress()->getGroupedAllShippingRates();
    }
}