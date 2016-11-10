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
 * @description Observer for
 *                  - <sales_convert_quote_address_to_order> (frontend)
 *                  - <sales_order_shipment_save_before> (adminhtml)  not implemented
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */
class Man4x_MondialRelay_Model_Observer
{    
    /**
     * Observer for <sales_convert_quote_address_to_order> admin/frontend event
     * For Pickup MondialRelay shipping method:
     *  - Append pickup code to shipping code
     *  - Replace customer shipping address with the selected pickup address
     */
    public function replaceShippingAddress($observer)
    {

        $_order = $observer->getEvent()->getOrder();
        $_address = $observer->getEvent()->getAddress();
        $_carrier = $_order->getShippingCarrier();

        // Carrier is Mondial Relay and address is a shipping address
        if (($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Pickup) && ('shipping' === $_address->getAddressType()))
        {
            // We recover selected pickup data set in session during pickup selection
            $_session = $this->_isAdmin() ? Mage::getSingleton('adminhtml/session') : Mage::getSingleton('checkout/session');

            if ($_selectedPickupData = $_session->getData('selected_pickup', false))
            {               
                // We update order SM to be formated as mondialrelaypickup_XXX_######
                $_fullShippingMethod = $_address->getShippingMethod() . '_' . $_selectedPickupData['id'];
                $_order->setShippingMethod($_fullShippingMethod);
                
                // We manually change shipping address to the pickup's one
                $_pAddress = $_address;
                $_pAddress->setCompany($_selectedPickupData['name'])
                        ->setStreet($_selectedPickupData['street'])
                        ->setPostcode($_selectedPickupData['postcode'])
                        ->setCity($_selectedPickupData['city'])
                        ->setCountryId($_selectedPickupData['country'])
                        ->setShippingMethod($_fullShippingMethod);

                Mage::helper('checkout/cart')->getQuote()->setShippingAddress($_pAddress);
                
                // We clear pickup data in session
                $_session->unsetData('selected_pickup');
            }
        }
    }

    /**
     * Observer for <sales_order_shipment_track_save_after> admin event
     * If track is a Mondial Relay one, we register track number in the registry to be available in ShippingController
     * (massShippingWsAction)
     */
    public function registerMondialRelayTrackNumber($observer)
    {
        $_track = $observer->getEvent()->getTrack();
        if (false !== strpos($_track->getCarrierCode(), 'mondialrelay'))
        {
            if (Mage::registry('mondialrelay_tracking_number'))
            {
                Mage::unregister('mondialrelay_tracking_number');
            }
            Mage::register('mondialrelay_tracking_number', $_track->getTrackNumber());
        }
    }

    
    private function _isAdmin()
    {
        if (Mage::app()->getStore()->isAdmin())
        {
            return true;
        }

        if (Mage::getDesign()->getArea() == 'adminhtml')
        {
            return true;
        }

        return false;
    }
}