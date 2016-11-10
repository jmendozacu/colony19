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
 * @desc        Available values for conditions regarding Mondial Relay rates
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Model_System_Config_Source_Condition {

    public function toOptionArray()
    {
        $_arr = array(
            array(
                'value' => 'package_weight',
                'label' => Mage::helper('mondialrelay')->__('Rate vs weight/destination'),
                ),
            array(
                'value' => 'package_value',
                'label' => Mage::helper('mondialrelay')->__('Rate vs package value/destination'),
                ),
            array(
                'value' => 'package_qty',
                'label' => Mage::helper('mondialrelay')->__('Rate vs package total items/destination'),
                ),
        );
        return $_arr;
    }

}