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
 * @desc        Admin grid render for Mondial Relay shipping way (shipment or return)
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */


class Man4x_MondialRelay_Block_Adminhtml_Grid_Renderer_Shippingway
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $_arrow = ('reverse' === $row->getData('description')) ? '&larr;' : '&rarr;';
        $_color = ('reverse' === $row->getData('description')) ? '#F00' : '#0F0';

        return '<span style="font-weight: bold; font-size: 16px; color:' . $_color . ';">' . $_arrow . '</span>';
    }
}
