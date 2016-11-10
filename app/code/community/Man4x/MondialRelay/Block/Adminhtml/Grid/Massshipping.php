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
 * @desc        Mass shipping grid container
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Block_Adminhtml_Grid_Massshipping
	extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'mondialrelay';
        $this->_controller = 'adminhtml_grid_massshipping'; // => grid = mondialrelay_block_adminhtml_grid_massshipping_grid
        $this->_headerText = 'Mondial Relay' . ' - ' . Mage::helper('mondialrelay')->__('Mass Shipping');
        parent::__construct();
        $this->_removeButton('add');        
    }


    protected function _prepareLayout()
    {       
        parent::_prepareLayout();
        
        // We create the block serializer for real weights and append it to root
        $_serializer = $this->getLayout()->createBlock('adminhtml/widget_grid_serializer', 'serializer');
        // public function initSerializerBlock($grid, $callback, $hiddenInputName, $reloadParamName = 'entityCollection')
        $_serializer->initSerializerBlock('adminhtml_grid_massshipping.grid', 'getRealWeights', 'real_weight_input', 'order_ids');
        $_serializer->addColumnInputName('real_weight');
        $this->getLayout()->getBlock('root')->setChild('serializer', $_serializer);
        
	return $this;
    }
    
    protected function _toHtml()
    {
        $_html = parent::_toHtml();
        $_html .= $this->getLayout()->getBlock('root')->getBlockHtml('serializer');
	//$_html .= $this->getLayout()->getBlock('serializer')->_toHtml();

        return $_html;
    }
}