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
 * @desc        Mass returning grid
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Block_Adminhtml_Grid_Massreturning_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_massreturning_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /*
     *  Inherited from Mage_Adminhtml_Block_Widget_Grid - called from widget/grid.phtml$
     *  We add a target="_blank" to massaction form to open shipping labels pdf in a new tab
 
    protected function getAdditionalJavascript()
    {        
        $_js = '$("' . $this->getMassactionBlock()->getHtmlId() . '-form").writeAttribute("target", "_blank");';
        return $_js;
    }
     */
    
    protected function _getCollectionClass()
    {
        return 'sales/order_shipment_grid_collection';
    }

    // We only display shipments which doesn't have any Mondial Relay reverse track
    private function _getShipmentCollection()
    {
        $_collection = Mage::getResourceModel($this->_getCollectionClass());
        return $_collection;
    }

    protected function _prepareCollection()
    {
        $_collection = $this->_getShipmentCollection()        
                ->join( 'order',
                        'main_table.order_increment_id = order.increment_id',
                        array(
                            'shipping_method',
                            'grand_total',
                            'store_currency_code'
                        )
                );
        $this->setCollection($_collection);       
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {              
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store',
                array(
                    'header'            => Mage::helper('sales')->__('Purchased from (store)'),
                    'index'             => 'store_id',
                    'type'              => 'store',
                    'store_view'        => true,
                    'filter_index'      => 'main_table.store_id',
                    'display_deleted'   => true,
                    )
                );
        }

        $this->addColumn(
            'increment_id',
            array(
                'header'            => Mage::helper('sales')->__('Shipment #'),
                'index'             => 'increment_id',
                'type'              => 'text',
                )
            );

        $this->addColumn(
            'created_at',
            array(
                'header'        => Mage::helper('sales')->__('Date Shipped'),
                'index'         => 'created_at',
                'type'          => 'datetime',
                )
            );

        $this->addColumn(
            'shipping_name',
            array(
                'header'    => Mage::helper('sales')->__('Ship to Name'),
                'index'     => 'shipping_name',
                'type'      => 'text',
                )
            );

        $this->addColumn(
            'grand_total',
            array(
                'header'            => Mage::helper('sales')->__('Grand Total'),
                'index'             => 'grand_total',
                'type'              => 'currency',
                'currency'          => 'store_currency_code',
                'filter_index'      => 'order.grand_total'
                )
            );

        $this->addColumn(
            'order_increment_id',
            array(
                'header'    => Mage::helper('sales')->__('Order #'),
                'index'     => 'order_increment_id',
                'type'      => 'number',
                )
            );

        $this->addColumn(
            'shipping_method',
            array(
                'header'                    => Mage::helper('sales')->__('Shipping Method'),
                //'index'                     => 'shipping_method',
                'type'                      => 'options',
                'options'                   => $this->_getCarrierCodes(),
                'renderer'                  => 'Man4x_MondialRelay_Block_Adminhtml_Grid_Renderer_Shippingcarrier',
                'filter_condition_callback' => array($this, '_setCarrierFilter'),

                )
            );
        
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    // Get URL location for click on row
    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/shipment/actions/view'))
        {
            return $this->getUrl('adminhtml/sales_order_shipment/view', array('shipment_id' => $row->getId()));
        }
        return false;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('shipment_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        // Labels printing
        $this->getMassactionBlock()->addItem(
            'massreturning_ws',
            array(
                'label'         => Mage::helper('sales')->__('Mass Returning'),
                'url'           => $this->getUrl('adminhtml/mondialrelay_shipping/massReturningWs'),
                'additional'    => array(
                    /*
                    'download'  => array(
                        'name'      => 'download',
                        'type'      => 'checkbox',
                        'value'     => '1',
                        'label'     => Mage::helper('mondialrelay')->__('Download return shipping label'),
                    ),
                     */
                    'notify'    => array(
                        'name'      => 'notify',
                        'type'      => 'checkbox',
                        'value'     => '1',
                        'label'     => Mage::helper('mondialrelay')->__('Email return shipping label to customer'),
                        'checked'   => true,
                    )
                )
            )
        );

        return $this;
    }

    // Ajax grid reloading
    public function getGridUrl()
    {
        return $this->getUrl('*/*/ajaxmassreturninggrid', array('_current'=>true));
    }
    
    /*
     * Get carriers' code
     */
    protected function _getCarrierCodes()
    {
        $_carrierCodes = array();
        
        $_carriers = Mage::getSingleton('shipping/config')->getAllCarriers();
        foreach (array_keys($_carriers) as $_carrier)
        {
            if ('mondialrelay' !== $_carrier)
            {
                $_carrierCodes[$_carrier] = Mage::getStoreConfig('carriers/' . $_carrier . '/title');
            }
        }
        
        return $_carrierCodes;       
    }
    
    /*
     * Filter collection according to carrier
     */
    protected function _setCarrierFilter($collection, $column)
    {
        if ($_value = $column->getFilter()->getValue())
        {
            $_value .= '%';
            $this->getCollection()->getSelect()->where('shipping_method like ?', $_value);
        }
        
        return $this;
    }

}
