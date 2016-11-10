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
 * @desc        Mondial Relay pickup selection block
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */


class Man4x_MondialRelay_Block_Pickupselectionform
    extends Mage_Core_Block_Template
{

    /**
     * Block rendering
     * If Mondial Relay pickup shipping method isn't active, we return an empty block
     * 
     * @return string
     */
    protected function _toHtml()
    {
        if (Mage::getStoreConfigFlag('carriers/mondialrelaypickup/active', $this->_getStore()))
        {
            return parent::_toHtml();
        }
        return '';
    }

    // Determines if pickup selection is configured as on-map or on-list
    // Note: return int value to be handled in JS
    public function onMapSelection()
    {
        $_onMap = Mage::getStoreConfig('carriers/mondialrelaypickup/map_selection', $this->_getStore()) ? 1 : 0;
        return $_onMap;
    }

    // Determines if current store is secure (to know if pickup pictures can be displayed)
    public function isStoreSecure()
    {
        $_isSecure = $this->_getStore()->isCurrentlySecure() ? 'true' : 'false';
        return $_isSecure;
    }

    // Build the allowed country list for pick-up deliveries regarding module config
    // Argument is mandatory to comply to the parent method
    public function getCountryHtmlSelect($type)
    {
        $_cCodes = explode(',', Mage::getStoreConfig('carriers/mondialrelaypickup/specificcountry', $this->_getStore()));
        
        $_allowedCountries = array();
        foreach($_cCodes as $_cCode)
        {
            $_allowedCountries[$_cCode] = Mage::app()->getLocale()->getCountryTranslation($_cCode);
        }
        asort($_allowedCountries);
                
        $_select = $this->getLayout()->createBlock('core/html_select')
            ->setName('psf-country')
            ->setId('psf-country')
            ->setTitle(Mage::helper('checkout')->__('Country'))
            ->setClass('validate-select')
            ->setOptions($_allowedCountries)
            ->setExtraParams('onchange=\'window.alert("' . $this->__(
                    'Notice: shipping fee may be different for a pick-up located in another country') . '");\'');

        return $_select->getHtml();
    }
    
    public function getPickupGatheringUrl()
    {
        /*
         *  We use the _direct parameter here for Magento 1.4 compatibility: without it, returned url requested from back-end
         *  (i.e. for a back-end order) is an admin-area URL
         */
	$_url = Mage::getUrl('', 
                    array(
                        '_secure' => Mage::app()->getFrontController()->getRequest()->isSecure(),
                        '_direct' => 'mondialrelay/index/gatherpickups',
                        '_store' => $this->_getStore()->getStoreId(),
                    )
                );
        
	return $_url;
    }
    
    public function getGoogleMapUrl()
    {
        $_store = $this->_getStore();
        $_url = '//maps.googleapis.com/maps/api/js?key='
                . Mage::getStoreConfig('carriers/mondialrelaypickup/api_key', $_store)
                . '&callback=ongooglemaploaded';
        
        return $_url;
    }
        
    // Get order store
    // When a new order has just been created from the back-office, concerned store
    // is not defined yet for the current quote so we use the default store then 
    private function _getStore()
    {
        if (Mage::helper('mondialrelay')->isAdmin())
        {
            if ($_store = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getStore())
            {
                if ($_store->getId())
                {
                    return $_store;
                }
            }
            
            return Mage::app()->getDefaultStoreView();
        }
        
        return Mage::app()->getStore();        
    }
}