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
 * @desc        Module version field
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */


class Man4x_MondialRelay_Block_Adminhtml_System_Config_Form_Field_Informations
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
            $_version = '';
            $_module = Mage::getConfig()->getNode('modules/Man4x_MondialRelay');
            if ($_module)
            {
                $_moduleConfig = $_module->asArray();
                $_version = $_moduleConfig['version'];
                $_version .= ' (' . ($_moduleConfig['active'] ? 'enabled' : 'disabled') . ')';                
            }
            
            return $_version;
	}
}
