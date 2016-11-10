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
 * @desc        Custom exception
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 *
 */

class Man4x_MondialRelay_Exception
    extends Exception
{
    // Debug log file
    const LOG_FILE = 'man4x_mondialrelay_debug.log';
    
    // WS Error messages
    protected $_statError = array(
        '1' => 'Invalid Company Name',
        '2' => 'Missing Company Number',
        '3' => 'Invalid Company Account',
        '4' => '',
        '5' => 'Invalid Company File Number',
        '6' => '',
        '7' => 'Invalid Company Customer Number',
        '8' => '',
        '9' => 'Unknown/Non-unique City Name',
        '10' => 'Invalid Collection Type (1/D > Home -- 3/R > Relay)',
        '11' => 'Invalid Collection Relay Number',
        '12' => 'Invalid Collection Relay Country',
        '13' => 'Invalid Delivery Type (1/D > Home -- 3/R > Relay)',
        '14' => 'Invalid Delivery Relay Number',
        '15' => 'Invalid Delivery Relay Country',
        '16' => 'Invalid Country Code',
        '17' => 'Invalid Address',
        '18' => 'Invalid City',
        '19' => 'Invalid Post Code',
        '20' => 'Invalid Parcel Weight',
        '21' => 'Invalid Parcel Size (Length + Height)',
        '22' => 'Invalid Parcel Size',
        '23' => '',
        '24' => 'Invalid Mondial Relay Parcel Number',
        '25' => '',
        '26' => '',
        '27' => '',
        '28' => 'Invalid Collection Mode',
        '29' => 'Invalid Delivery Mode',
        '30' => 'Invalid Sender Address Line 1',
        '31' => 'Invalid Sender Address Line 2',
        '32' => '',
        '33' => 'Invalid Sender Address Line 3',
        '34' => 'Invalid Sender Address Line 4',
        '35' => 'Invalid Sender City',
        '36' => 'Invalid Sender Post Code',
        '37' => 'Invalid Sender Country',
        '38' => 'Invalid Sender Phone Number',
        '39' => 'Invalid Sender E-mail',
        '40' => 'No Available Action Without City / Post Code',
        '41' => 'Invalid Delivery Mode',
        '42' => 'Invalid COD Amount', // CRT = Contre-Remboursement ? (=> Cash On Delivery)
        '43' => 'Invalid COD Currency',
        '44' => 'Invalid Parcel Value',
        '45' => 'Invalid Parcel Value Currency',
        '46' => 'Exhausted Delivery Number Range',
        '47' => 'Invalid Parcel Number',
        '48' => 'Relay Multi-Piece Delivery is not Allowed',
        '49' => 'Invalid Collection or Delivery Mode',
        '50' => 'Invalid Recipient Address Line 1',
        '51' => 'Invalid Recipient Address Line 2',
        '52' => '',
        '53' => 'Invalid Recipient Address Line 3',
        '54' => 'Invalid Recipient Address Line 4',
        '55' => 'Invalid Recipient City',
        '56' => 'Invalid Recipient Post Code',
        '57' => 'Invalid Recipient Country',
        '58' => 'Invalid Recipient Phone Number',
        '59' => 'Invalid Recipient E-mail',
        '60' => 'Invalid Text Field',
        '61' => 'Invalid Top Notification',
        '62' => 'Invalid Delivery Instructions',
        '63' => 'Invalid Insurance',
        '64' => 'Invalid Setup TimeTemps de montage invalide',
        '65' => 'Invalid Appointment Top',
        '66' => 'Invalid Recovery Top',
        '67' => '',
        '68' => '',
        '69' => '',
        '70' => 'Invalid Relay Number',
        '71' => '',
        '72' => 'Invalid Sender Language',
        '73' => 'Invalid Recipient Language',
        '74' => 'Invalid Language',
        '75' => '',
        '76' => '',
        '77' => '',
        '78' => '',
        '79' => '',
        '80' => 'Tracking Code: Registered Parcel',
        '81' => 'Tracking Code: Mondial Relay Processing Parcel',
        '82' => 'Tracking Code: Delivered Parcel',
        '83' => 'Tracking Code: Anomaly',
        '84' => '(Tracking Code Reserved)',
        '85' => '(Tracking Code Reserved',
        '86' => '(Tracking Code Reserved)',
        '87' => '(Tracking Code Reserved)',
        '88' => '(Tracking Code Reserved)',
        '89' => '(Tracking Code Reserved)',
        '90' => 'AS400 Unavailability',
        '91' => 'Invalid Shipment Number',
        '92' => '',
        '93' => 'No Result After Sorting Plan',
        '94' => 'Nonexistent ParcelColis',
        '95' => 'Disabled Company Account',
        '96' => 'Bad Base Company Type',
        '97' => 'Invalid Security Key',
        '98' => 'Unavailable Service',
        '99' => 'Service Generic Error'
    );

    /**
     *  Constructor
     *  Translate STAT code to error message
     *  If debug mode is on, log error in log file
     *  If raised from admin store, add dump to error message
     * 
     *  @param string service
     *  @param int | string code
     *  @param array dump
     *  @return string
     */
    public function __construct ($service, $code, $dump)
    {
        $_message = is_numeric($code) ? $this->_convertStatToTxt($code) : $code . ' is missing in ' . $service . ' response';
        
        if (Mage::getStoreConfig('carriers/mondialrelay/debug_mode'))
        {
            $_dump = array(
                'service'   => $service,
                'call_dump' => $dump,
                'error_msg' => $_message
            );
            Mage::log($_dump, null, self::LOG_FILE, true);
            
            if ($this->_isAdmin())
            {
                $_message .= ' [Debug data >>> ' . print_r($dump, true) . ']';
            }
        }

        parent::__construct($_message, intval($code), null);       
    }
    
    /**
     * Convert WS error code into error message
     * 
     * @param string $stat
     * @return string 
     */
    private function _convertStatToTxt($stat)
    {
        $_errorMsg = Mage::helper('mondialrelay')->__(isset($this->_statError[$stat]) ? 
                $this->_statError[$stat] : 'Unknown Error' . ' - ' . $stat);
        
        return $_errorMsg;
    }

    /**
     * Determines if current store is admin or not
     * 
     * @return bool
     */
    private function _isAdmin()
    {
        return Mage::app()->getStore()->isAdmin() || Mage::getDesign()->getArea() === 'adminhtml';
    }
}
