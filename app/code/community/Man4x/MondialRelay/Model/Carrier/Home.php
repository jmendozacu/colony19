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
 * @desc        Mondial Relay carrier model class for home deliveries
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Model_Carrier_Home
    extends Man4x_MondialRelay_Model_Carrier_Abstract
{    
    
    /************
     * Properties
     ************/

    protected $_allMethods = array( 
        'HOM' => array(
            'name'                  => 'HOME mode',
            'data_config_suffix'    => '',
            'max_weight'            => 30000,
            'multipack'             => false,
            'default_reverse'       => 'mondialrelaypickup_REL',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
                'BE' => array('delay' => '2D'),
                'LU' => array('delay' => '2D'),
                'ES' => array('delay' => '2D'),
                'GB' => array('delay' => '2/3D'),
                'DE' => array('delay' => '2D'),
                'IT' => array('delay' => '4D'),
                'AT' => array('delay' => '2/3D'),
                'NL' => array('delay' => '2D'),
                'IE' => array('delay' => '3/4D'),
                'PT' => array('delay' => '3/4D'),
                'CZ' => array('delay' => '3D'),
                'SK' => array('delay' => '3D'),
                'HU' => array('delay' => '3D'),
                'MC' => array('delay' => '1D'),
                'SE' => array('delay' => '3/4D'),
                'FI' => array('delay' => '3/4D'),
                'DK' => array('delay' => '3/4D'),
            ),
        ),
        'LD1' => array(
            'name'                  => 'HOME1 mode',
            'data_config_suffix'    => '_ld',
            'max_weight'            => 70000,
            'multipack'             => true,
            'reverse'               => 'mondialrelayhome_CDR',
            'reverse_title'         => 'CDR mode',
            'default_reverse'       => 'mondialrelayhome_CDR',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
                'BE' => array('delay' => '1/2D'),
                'LU' => array('delay' => '1/2D'),
                'ES' => array('multipack' => false,'delay '=> '2/4D'),
            ),
        ),
        'LD2' => array(
            'name'                  => 'HOME2 mode',
            'data_config_suffix'    => '_ld',
            'max_weight'            => 130000,
            'multipack'             => true,
            'reverse'               => 'mondialrelayhome_CDS',
            'reverse_title'         => 'CDS mode',
            'default_reverse'       => 'mondialrelayhome_CDS',
            'country_settings'      => array(
                'FR' => array('delay' => '1D'),
                'BE' => array('delay' => '1/2D'),
                'LU' => array('delay' => '1/2D'),
                'ES' => array('multipack' => false,'delay' => '2/4D'),
            ),
        ),
    );
    
    protected $_code = 'mondialrelayhome';

    /**************************************
     * Mage_Shipping_Model_Carrier_Interface
     **************************************/
    
    /***********************************************
     * Mage_Shipping_Model_Carrier_Abstract overrides
     ***********************************************/

    /**
     * Processing additional validation to check if carrier applicable
     * If both LD1 and LD2 are available, we only keep LD2 since it's the suffisant method for a home delivery with appointment
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Carrier_Abstract | Mage_Shipping_Model_Rate_Result_Error | false
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
        // We get all available methods
        parent::proccessAdditionalValidation($request);
        
        if (isset($this->_rates['LD1']) && isset($this->_rates['LD2']))
        {
            unset($this->_rates['LD2']);
        }

        $this->_rates = array_reverse($this->_rates);
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
    final function _collectReturnParams(Mage_Shipping_Model_Shipment_Request $request)
    {
        $_params = parent::_collectReturnParams($request);
        $_params['ModeCol'] = $request->getShippingMode();

        return $_params;
    }

    /**
     * Get the Mondial Relay rate relevant for the given method / country / region / postcode / condition value / franco
     * Call parent method and add extra fee if specified and LD2 mode
     * 
     * @param Mage_Shipping_Model_Rate_Request request request
     * @param string mode
     * @return bool | float
     */
    protected function _getPrice($request, $mode)
    {
        $_price = parent::_getPrice($request, $mode);
        
        if ($_price && ('LD2' === $mode || 'LD1' === $mode))
        {
            // Determine if extra fee for ld2 applies
            $_extrafeeField = $this->getConfigData('extrafee_ld');
            $_extrafee = (float)$_extrafeeField;
            if ($_extrafee)
            {
                if (false !== strpos($_extrafeeField, '%'))
                {
                    $_price += .01 * $_extrafee * $_price;
                }
                else
                {
                    $_price += $_extrafee;
                }
            }
        }

        return $_price;
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
        $_record[16] = 'D'; // Shipping type (<R>elais, <D>omicile)
        
        $_csvLine = implode(self::CSV_SEPARATOR, $_record);
        $_csvLine .= self::CSV_EOL;
        return $_csvLine; 
    }
    
}