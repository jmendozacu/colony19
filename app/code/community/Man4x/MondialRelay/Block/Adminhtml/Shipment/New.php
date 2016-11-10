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
 * @block       MondialRelay_Block_Adminhtml_Shipment_New
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * 
 * This block is intended to manage new shipment for Mondial Relay orders.
 * It is added to the new shipment backend form through the <adminhtml_sales_order_shipment_new> handle of the
 * Man4x_MondialRelay.xml layout.
 * Its purpose is double, depending on multipacking availability (considering Mondial Relay specifications for the given method
 * and destination country) for the current shipment.
 *  - if multipacking is not available, it enables administrator to specify if a web service registration must be performed or not
 *  - if multipacking is available, it enables to skip the packaging form if administrator simply wants to make a single-parcel shipment
 * In both case, decision is made through a JS confirmation box. 
 */

class Man4x_MondialRelay_Block_Adminhtml_Shipment_New
    extends Mage_Adminhtml_Block_Sales_Order_View_Info
{    
    /**
     * Block rendering
     * If current order is not a Mondial Relay order, we skip rendering
     * 
     * @return string
     */
    protected function _toHtml()
    {
        $_result = false;
        $_order = $this->getOrder();
        if ($_order)
        {
            $_shippingMethod = explode('_', $_order->getShippingMethod());
            if (false !== strpos($_shippingMethod[0], 'mondialrelay'))
            {
                $_result = true;
            }
        }
        
        if ($_result)
        {
            return parent::_toHtml();
        }
        return '';
    }
    
    /**
     * Determines if multipacking is available for the current shipment, given its shipping mode and destination country
     * 
     * @return string
     */
    public function isMultipackAvailable()
    {
        $_result = 'false';
        $_order =  Mage::registry('current_shipment') ? Mage::registry('current_shipment')->getOrder() : null;

        if ($_order)
        {
            $_shippingMethod = explode('_', $_order->getShippingMethod());
            $_carrier = $_order->getShippingCarrier();
            if ($_carrier)
            {
                $_address = $_order->getShippingAddress();
                if ($_address)
                {
                    if ($_carrier->getMethodConfig($_shippingMethod[1], 'multipack', $_address->getCountryId()))
                        $_result = 'true';
                }
            }
        }

        return $_result;
    }
}
