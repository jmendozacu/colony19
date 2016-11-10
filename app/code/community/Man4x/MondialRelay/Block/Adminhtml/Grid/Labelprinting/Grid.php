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
 * @desc        Label printing grid. Enable the mass label printing through web service
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */

class Man4x_MondialRelay_Block_Adminhtml_Grid_Labelprinting_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_labelprinting_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        // Enables massaction checkbox to be valued with the desired field value (here track_number) instead of entity_id
        // See Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Massaction->render
        // and Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Checkbox->render
        $this->setMassactionIdFieldOnlyIndexValue(true);
    }

    /*
     *  Inherited from Mage_Adminhtml_Block_Widget_Grid - called from widget/grid.phtml$
     *  We add a target="_blank" to massaction form to open shipping labels pdf in a new tab
     */   
    protected function getAdditionalJavascript()
    {        
        $_js = '$("' . $this->getMassactionBlock()->getHtmlId() . '-form").writeAttribute("target", "_blank");';
        return $_js;
    }

    protected function _getCollectionClass()
    {
        return 'sales/order_shipment_track_collection';
    }

    // We only display Mondial Relay orders
    protected function _prepareCollection()
    {
        $_collection = Mage::getResourceModel($this->_getCollectionClass())
                ->join( array('s' => 'shipment_grid'),
                        'main_table.parent_id = s.entity_id',
                        array(
                            'store_id',
                            'total_qty',
                            'increment_id',
                            'order_increment_id',
                            'shipping_name',
                            's_created_at' => 'created_at'
                            )
                        )
                ->addFieldToFilter('carrier_code', array('like' => 'mondialrelay%'))
                ->addFieldToFilter('track_number', array('notnull' => true));

        $this->setCollection($_collection);       
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id',
                array(
                    'header'            => Mage::helper('sales')->__('Purchased From (Store)'),
                    'index'             => 'store_id',
                    'type'              => 'store',
                    'store_view'        => true,
                    'filter_index'      => 'store_id',
                    'display_deleted'   => true,
                    )
                );
        }

        $this->addColumn(
            'shipping_way',
            array(
                'header'                    => Mage::helper('mondialrelay')->__('Shipping way'),
                'type'                      => 'options',
                'options'                   => array(
                                                's' => '&rarr;',
                                                'r' => '&larr;'
                                            ),
                'renderer'                  => 'Man4x_MondialRelay_Block_Adminhtml_Grid_Renderer_Shippingway',
                'filter_condition_callback' => array($this, '_setShippingWayFilter'),
                'width'                     => '30px',
                )
            );

        $this->addColumn(
            'shipping_name',
            array(
                'header'    => Mage::helper('sales')->__('Ship to Name'),
                'type'      => 'text',
                'index'     => 'shipping_name',
                )
            );

        $this->addColumn(
            'created_at',
            array(
                'header'        => Mage::helper('mondialrelay')->__('Label created at'),
                'index'         => 'created_at',
                'filter_index'  => 'main_table.created_at',
                'type'          => 'datetime',
                )
            );

        $this->addColumn(
            'increment_id',
            array(
                'header'    => Mage::helper('sales')->__('Shipment #'),
                'index'     => 'increment_id',
                'type'      => 'text',
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
            'total_qty',
            array(
                'header'    => Mage::helper('sales')->__('Total Qty'),
                'index'     => 'total_qty',
                'type'      => 'number',
                )
            );

        $this->addColumn(
            'track_number',
            array(
                'header'    => Mage::helper('sales')->__('Track Number'),
                'type'      => 'text',
                'index'     => 'track_number',
                )
            );

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel'));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return '';
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('track_number');
        $this->getMassactionBlock()->setFormFieldName('tracking_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        // Labels printing
        $this->getMassactionBlock()->addItem(
            'pdfshipments_order',
            array(
                'label' => Mage::helper('sales')->__('Labels Printing'),
                'url'   => $this->getUrl('adminhtml/mondialrelay_shipping/massLabelPrinting'),
                )
            );

        return $this;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/ajaxMassLabelPrintingGrid', array('_current' => true));
    }
    
    /*
     * Filter collection according to shipping way
     */
    protected function _setShippingWayFilter($collection, $column)
    {
        if ($_value = $column->getFilter()->getValue())
        {
            switch($_value)
            {
                case 'r':
                    $this->getCollection()->getSelect()->where('description = "reverse"');
                    break;
                case 's':
                    $this->getCollection()->getSelect()->where('description != "reverse"');
                    break;
            }
        }
        
        return $this;
    }
}
