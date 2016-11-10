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
 * @desc        Mondial Relay carrier model class for pickup deliveries
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Model_Carrier_Pickup
    extends Man4x_MondialRelay_Model_Carrier_Abstract
{    
     
    /*******************
     * Properties
     *******************/

    protected $_allMethods = array( 
        '24R' => array(
            'name'                  => 'L mode',
            'max_weight'            => 30000,
            'multipack'             => false,
            'reverse'               => 'mondialrelaypickup_REL',
            'reverse_title'         => 'REL mode',
            'default_reverse'       => 'mondialrelaypickup_REL',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
                'BE' => array('delay' => '1/2D'),
                'LU' => array('delay' => '1/2D'),
                'ES' => array('delay' => '3/4D'),
                'GB' => array('delay' => '3/4D'),
                'DE' => array('delay' => '2D'),
                'AT' => array('delay' => '3/4D'),
            )
        ),
        '24L' => array(
            'name'                  => 'XL mode',
            'max_weight'            => 50000,
            'multipack'             => true,
            'default_reverse'       => 'mondialrelayhome_CDR',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
            ),
        ),
        '24X' => array(
            'name'                  => 'XXL mode',
            'max_weight'            => 70000,
            'multipack'             => true,
            'default_reverse'       => 'mondialrelayhome_CDR',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
            ),
        ),
        'DRI' => array(
            'name'                  => 'Colisdrive mode',
            'max_weight'            => 130000,
            'multipack'             => true,
            'default_reverse'       => 'mondialrelayhome_CDS',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
                'BE' => array('delay' => '1D'),
            ),
        ),
    );
        
    protected $_code = 'mondialrelaypickup';
    
    /******************
     * Static functions
     ******************/
    
    /*
     * Get action mode
     * Notice: if specified for other countries than France -> no result
     * 
     * @param int weight
     * @return string
     */
    public static function getActionMode($weight) 
    {
        $_actionMode = $weight > 70000 ? 'DRI' : '';
        return $_actionMode;
    }
    
    /**************************************
     * Mage_Shipping_Model_Carrier_Interface
     **************************************/
        
    /***********************************************
     * Mage_Shipping_Model_Carrier_Abstract overrides
     ***********************************************/

    /**
     * Processing additional validation to check if carrier applicable
     * Here, we keep only the most restrictive shipping method and remove others
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Carrier_Abstract | Mage_Shipping_Model_Rate_Result_Error | false
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        parent::proccessAdditionalValidation($request);
        
        if (!empty($this->_rates))
        {
            while (count($this->_rates) > 1)
            {
                array_pop($this->_rates);
            }
        }

        return $this;
    }
    
    /***********************************************
     * Man4x_MondialRelay_Model_Carrier_Abstract overrides
     ***********************************************/

    /**
     *  Get params for MR Web Service WSI2_CreationExpedition
     *  !!! Keep parameters in strict order !!!
     * 
     *  @param Mage_Shipping_Model_Shipment_Request request
     *  @return array
     */
    final function _collectShipmentParams(Mage_Shipping_Model_Shipment_Request $request)
    {
        $_params = parent::_collectShipmentParams($request);
    
        // Shipping Method reduced as method by Mage_Shipping_Model_Shipping->requestToShipment (e.g. 24R_00000)
        $_shippingMethod = explode('_', $request->getShippingMethod());
        $_params['LIV_Rel'] = $_shippingMethod[1];

        return $_params;
    }
    
    /**
     *  Get params for MR Web Service WSI2_CreationExpedition
     *  !!! Keep parameters in strict order !!!
     * 
     *  @param Mage_Shipping_Model_Shipment_Request request
     *  @return array
     */
    final function _collectReturnParams(Mage_Shipping_Model_Shipment_Request $request)
    {
        $_params = parent::_collectReturnParams($request);
        $_params['ModeCol'] = 'REL';
        $_params['COL_Rel'] = $request->getCollectionPickupId();
        $_params['COL_Rel_Pays'] = $request->getCollectionPickupCountryId();

        return $_params;
    }
       
    /**
     *  Get params for CSV export
     *  !!! Keep parameters in strict order !!!
     * 
     *  @param Mage_Sales_Model_Order $order
     *  @return string
     */
    public function getFlatFileData(Mage_Sales_Model_Order $order)
    {
        $_record = parent::getFlatFileData($order);
        $_record[15] = 'R'; // Shipping type (<R>elais, <D>omicile)
        
        $_method = explode('_', $order->getShippingMethod());
        $_record[16] = $_method[2]; // Pickup ID
        
        $_csvLine = implode(self::CSV_SEPARATOR, $_record);
        $_csvLine .= self::CSV_EOL;
        
        return $_csvLine; 
    }
    

}