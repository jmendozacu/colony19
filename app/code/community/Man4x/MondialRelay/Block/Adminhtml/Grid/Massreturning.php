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
 * @desc        Mass label printing grid container
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Block_Adminhtml_Grid_Massreturning
	extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup = 'mondialrelay';
        $this->_controller = 'adminhtml_grid_massreturning'; // => grid = mondialrelay_block_adminhtml_grid_massreturning
        $this->_headerText = 'Mondial Relay' . ' - ' . Mage::helper('mondialrelay')->__('Mass Returning');
        parent::__construct();
        $this->_removeButton('add');
    }
}