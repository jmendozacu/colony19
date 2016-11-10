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
 * @desc        Mass shipping grid. Enable mass shipping (through web service or flat file)
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * 
 * This block manages the mass shipment grid linked to a mass action.
 * In order to let administrator set "real weight" for each shipment, we also link a serializer to the grid.
 * Normally, serializer must be included in a form to work properly. Here we add some logic to do without a form by:
 *  - adding the 'checkbox' class to the massaction checkbox (see serializerController.rowInit in grid.js)
 *  - interleaving before massaction action a JS code to include serialized input in the POST data (thanks to a simple string
 * replacement on output HTML)
 * 
 */

class Man4x_MondialRelay_Block_Adminhtml_Grid_Massshipping_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_massshipping_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(false);
    }

    /*
     * We add the 'checkbox' class to the massaction checkbox to use it as selector and enable serialization (see in 
     * grid.js serializerController.rowInit)
     * Then we bypass the massaction [validate] action to include the hidden serialized field in POST data 
     */
    protected function _afterToHtml($html)
    {
        $html = str_replace('massaction-checkbox', 'massaction-checkbox checkbox', $html);
        
        $html = str_replace(    "sales_massshipping_grid_massactionJsObject.apply()",
                                "includeSerializedField(sales_massshipping_grid_massactionJsObject)",                                                            
                                $html);
        return $html;
    }

    /*
     * Inherited from Mage_Adminhtml_Block_Widget_Grid - called from widget/grid.phtml
     * We deplace the hidden serialized input into grid form to be submitted and we call the standard form action
     * We also add a target="_blank" to massaction form to open shipping labels pdf in a new tab
     */
    protected function getAdditionalJavascript()
    {
        $_js =
<<<JSCODE
                function includeSerializedField(grid) {
                    new Insertion.Bottom(   grid.formHiddens.parentNode,
                                            grid.fieldTemplate.evaluate(
                                                {name: "real_weight_input",
                                                 value: $$("input[name=real_weight_input]")[0].getValue()}
                                            )
                                        );
                    grid.apply();
                }
JSCODE;
                
        //$_js .= '$("' . $this->getMassactionBlock()->getHtmlId() . '-form").writeAttribute("target", "_blank");';
                
        return $_js;
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_invoice_grid_collection';
    }

    // We only display Mondial Relay orders whose status is 'processing'
    private function _getOrderCollection()
    {
        $_collection = Mage::getResourceModel($this->_getCollectionClass())
                ->join( 'order',
                        'main_table.order_increment_id = order.increment_id',
                        array('shipping_method', 'status', 'entity_id', 'weight', 'store_currency_code')
                        )
                ->addAttributeToFilter('shipping_method', array("like" => 'mondialrelay%'))
                ->addAttributeToFilter('status', 'processing');
        return $_collection;
    }
    
    protected function _prepareCollection()
    {
        $_collection = $this->_getOrderCollection();
        $this->setCollection($_collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                array(
                    'header'            => Mage::helper('sales')->__('Purchased From (Store'),
                    'index'             => 'store_id',
                    'type'              => 'store',
                    'store_view'        => true,
                    'filter_index'      => 'main_table.store_id',
                    'display_deleted'   => true,
                    )
                );
        }
        
        $this->addColumn(
            'order_increment_id',
            array(
                'header'        => Mage::helper('sales')->__('Order #'),
                'width'         => '80px',
                'type'          => 'text',
                'index'         => 'order_increment_id',
                'filter_index'  => 'main_table.order_increment_id',
                )
            );

        $this->addColumn(
            'created_at',
            array(
                'header'        => Mage::helper('sales')->__('Invoice Date'),
                'index'         => 'created_at',
                'filter_index'  => 'main_table.created_at',
                'type'          => 'datetime',
                'width'         => '100px',
                )
            );


        $this->addColumn(
            'billing_name',
            array(
                'header'    => Mage::helper('sales')->__('Bill to Name'),
                'index'     => 'billing_name',
                )
            );

        $this->addColumn(
            'base_grand_total',
            array(
                'header'            => Mage::helper('sales')->__('Grand Total'),
                'index'             => 'grand_total',
                'type'              => 'currency',
                'currency'          => 'store_currency_code',
                'filter_index'      => 'main_table.grand_total'
                )
            );

        $this->addColumn(
            'shipping_mode',
            array(
                'header'            => Mage::helper('sales')->__('Shipping'),
                'index'             => 'shipping_mode',
                'renderer'          => 'Man4x_MondialRelay_Block_Adminhtml_Grid_Renderer_Shippingmode',
                'width'             => '50px',
                'align'             => 'center',
                )
            );

        // @TODO: see if we can remove from grid
        /*
        $this->addColumn(
            'carrier',
             array(
                'header'            => Mage::helper('sales')->__('Carrier'),
                'index'             => 'shipping_method',
                'column_css_class'  => 'no-display country-id',
                'header_css_class'  => 'no-display'
                )
            );
         
         */

        $this->addColumn(
            'weight',
             array(
                'header'    => Mage::helper('mondialrelay')->__('Weight (calculated)'),
                'index'     => 'weight',
                )
            );

        $this->addColumn(
            'real_weight',
            array(
                'header'            => Mage::helper('mondialrelay')->__('Weight (real)'),
            	'type'              => 'input',
                'name'              => 'real_weight',
            	'validate_class'    => 'validate-not-negative-number',
                'editable'          => true,
                )
            );        

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    // Get URL location for click on row -> order
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view'))
        {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getOrderId()));
        }
        return false;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem(
            'massshipping_order_ws',
            array(
                'label'         => Mage::helper('mondialrelay')->__('Mass Shipping (Web Service)'),
                'url'           => $this->getUrl('adminhtml/mondialrelay_shipping/massShippingWs'),
                'additional'    => array(
                    'notify'    => array(
                        'name'      => 'notify',
                        'type'      => 'checkbox',
                        'value'     => '1',
                        'label'     => Mage::helper('mondialrelay')->__('Notify shipment to customers'),
                        'checked'   => true,
                    )
                )
            )
        );
        
        $this->getMassactionBlock()->addItem(
            'massshipping_order_cvs',
            array(
                'label'     => Mage::helper('mondialrelay')->__('Mass Shipping (Flat File)'),
                'url'       => $this->getUrl('adminhtml/mondialrelay_shipping/massShippingCvs'),
            )
        );

        return $this;
    }

    // Ajax grid refreshing URL
    public function getGridUrl()
    {
       return $this->getUrl('*/*/ajaxmassshippinggrid', array('_current'=>true));
    }

    // Called by the linked widget block serializer to get initial values (here, the package calculated weight)
    public function getRealWeights()
    {
        $_realWeights = array();
        $_savedWeights = array();
        
        if (Mage::getSingleton('adminhtml/session')->hasMondialRelayRealWeight())
        {
            $_savedWeights = Mage::getSingleton('adminhtml/session')->getMondialRelayRealWeight();
	}
                
        foreach($this->_getOrderCollection()->load() as $_order)
        {
            $_realWeights[$_order->getId()] = array(
                'real_weight' => (isset($_savedWeight[$_order->getId()]) ?
                    $_savedWeight[$_order->getId()] : $_order->getWeight())
            );
        }
        return $_realWeights;
    }
}
