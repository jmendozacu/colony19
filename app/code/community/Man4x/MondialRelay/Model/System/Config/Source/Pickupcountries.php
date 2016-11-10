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
 * @desc        Available countries for pickup delivery
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Model_System_Config_Source_Pickupcountries
{
    private function _getCountryName($code)
    {
        return Mage::app()->getLocale()->getCountryTranslation($code);
    }
    
    public function toOptionArray($isMultiselect=false)
    {
        $_allowedCountries = array();
        
        foreach (Mage::getSingleton('mondialrelay/carrier_pickup')->getAllMethods() as $_mode => $_config)
        {
            foreach ($_config['country_settings'] as $_country => $__c)
            {
                if (!in_array($_country, $_allowedCountries))
                {
                    $_allowedCountries[]= $_country;
                }
            }
        }

        $_countryNames = array();
        foreach ($_allowedCountries as $_country)
        {
            $_countryNames[$_country] = $this->_getCountryName($_country);
        }
        asort($_countryNames);

        $_options = array();        
        foreach ($_countryNames as $_code => $_name)
        {
            $_options[] = array('value' => $_code, 'label' => $_name);
        }      
        
        return $_options;
    }
}
