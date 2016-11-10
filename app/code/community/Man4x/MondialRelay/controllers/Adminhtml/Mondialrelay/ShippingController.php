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
 * @desc        Mondial Relay mass shipping / mass label printing controller for admin
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';

class Man4x_MondialRelay_Adminhtml_MondialRelay_ShippingController
    extends Mage_Adminhtml_Sales_Order_ShipmentController
{
    /**
     * Save shipment for Mondial Relay shipments
     * That's a copy of inherited saveAction method but without redirection in case of error nor ajax response
     *
     * @return null
     */
    public function _saveAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        if (!empty($data['comment_text']))
        {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }

        try
        {
            $shipment = $this->_initShipment();
            if (!$shipment)
            {
                $this->_forward('noRoute');
                return;
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text']))
            {
                $shipment->addComment($data['comment_text'], isset($data['comment_customer_notify']), isset($data['is_visible_on_front']));
                if (isset($data['comment_customer_notify']))
                {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email']))
            {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

            $this->_createShippingLabel($shipment);

            $this->_saveShipment($shipment);

            $shipment->sendEmail(!empty($data['send_email']), $comment);

            $shipmentCreatedMessage = $this->__('Mondial Relay shipping label successfully created for order #%s',
                    $shipment->getOrder()->getIncrementId());
            $this->_getSession()->addSuccess($shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        }
        catch (Exception $e)
        {
            $this->_getSession()->addError($e->getMessage());
        }
    }

    /**
     * Additional initialization
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Man4x_MondialRelay');
    }

    /***************
     * Mass shipping 
     ***************/

    /*
     * Grid for mass shipping
     */
    public function massShippingGridAction()
    {
        $this->_title($this->__('Mondial Relay'))->_title($this->__('Mass Shipping'));
        $this->loadLayout()
                ->_setActiveMenu('sales/pointsrelais')
                ->_addContent($this->getLayout()->createBlock('mondialrelay/adminhtml_grid_massshipping'))
                ->renderLayout();
    }

    /*
     * AJAX mass shipping grid
     */
    public function ajaxMassShippingGridAction()
    {
        $this->loadLayout('empty');
        
        $this->getLayout()->createBlock('core/text_list', 'root', array('output' => 'toHtml'));
        $_grid = $this->getLayout()
                ->createBlock('mondialrelay/adminhtml_grid_massshipping_grid')
                ->setOrderIds($this->getRequest()->getPost('order_ids', null));
        $this->getLayout()->getBlock('root')->append($_grid);
        
        $_formkey = $this->getLayout()->createBlock('core/template', 'formkey')->setTemplate('formkey.phtml');
        $this->getLayout()->getBlock('root')->append($_formkey);
        
        $this->getResponse()->setBody($this->getLayout()->getBlock('root')->toHtml());
    }

    /*
     * Mondial Relay mass shipping (Web Service)
     * From Admin > Sales > Mondial Relay > Mass Shipment, Action = mass shipping (web service)
     */
    public function massShippingWsAction()
    {
        $_notify = (bool)$this->getRequest()->getPost('notify') ? true : '';
        
        // We recover real weights defined in the mass shipping grid and save them in admin session
        // to be available in carrier requestOfShipment method
        $_realWeights = Mage::helper('adminhtml/js')->decodeGridSerializedInput(
                $this->getRequest()->getPost('real_weight_input', ''));       
        Mage::getSingleton('adminhtml/session')->setMondialRelayRealWeight($_realWeights);

        $_orderIds = (array) $this->getRequest()->getPost('order_ids');      
        $_trackNumbers = array();
        
        // We try to ship every order...      
        foreach ($_orderIds as $_orderId)
        {
            $_order = Mage::getModel('sales/order')->load($_orderId);
            if (!$_order->getId())
            {
                continue;
            }

            // We build a "fake" request to comply with ShipmentController->saveAction() and force label creation
            $this->getRequest()->setParam('order_id', $_orderId);
            $this->getRequest()->setParam('packages', 0);
                        
            // We manually add all items to post
            $_items = array();
            foreach ($_order->getAllItems() as $_item)
            {
                if (!$_item->getIsVirtual() && !$_item->getParentItemId())
                {
                    $_items[$_item->getId()] = $_item->getQtyOrdered() * 1;
                }
            }
            $_shipment = array(
                'send_email'            => $_notify,
                'create_shipping_label' => true,
                'items'                 => $_items,
            );
            
            $this->getRequest()->setPost('shipment', $_shipment);
          
            // We call our derived save action
            $this->_saveAction();
            
            // We try to get Mondial Relay track number set in Man4x_MondialRelay_Model_Observer->registerMondialRelayTrackNumber
            if ($_trackNumber = Mage::registry('mondialrelay_tracking_number'))
            {
                $_trackNumbers[]= $_trackNumber;
                Mage::unregister('mondialrelay_tracking_number');
            }

            // We unregister 'current_shipment' data to continue mass shipment
            Mage::unregister('current_shipment');
        }

        // Remove real weight array from session
        Mage::getSingleton('adminhtml/session')->unsMondialRelayRealWeight();

        // If there are orders have been correctly registered, we put tracks in request params and call massLabelPinting action
        // Otherwise we redirect to mass shipping grid to display error messages
        if (count($_trackNumbers))
        {
            $this->getRequest()->setParam('tracking_ids', $_trackNumbers);
            $this->massLabelPrintingAction();
        }
        else
        {
            $this->_redirect('*/*/massShippingGrid');
        }
    }

     /*
      * Mondial Relay mass shipping (Flat File)
      * From Admin > Sales > Mondial Relay > Mass Shipment, Action = mass shipping (flat file)
      * @TODO: manage file format and file extension
      */
    public function massShippingCvsAction()
    {
        $_file = '';
        $_fileName = 'mondialrelay_export_' . Mage::getSingleton('core/date')->date('Ymd_His') . '.txt';

        $_orderIds = (array) $this->getRequest()->getPost('order_ids');
        
        if (count($_orderIds) > 100)
        {
            $this->_getSession()->addError($this->__('Too many orders: 100 max. by export file.'));
            return $this->_redirectReferer();
        }
        
        foreach ($_orderIds as $_orderId)
        {
            $_order = Mage::getModel('sales/order')->load($_orderId);
            if ($_order->getId())
            {
                $_carrier = $_order->getShippingCarrier();
                if ($_carrier instanceof Man4x_MondialRelay_Model_Carrier_Abstract)
                {
                    $_file .= $_carrier->getFlatFileData($_order);
                }
            }
        }
        
        $_fileCharset = 'ISO-8859-1'; // possibly unicode
        $_file = utf8_decode($_file);
        $_fileMimeType = 'text/plain'; // possibly 'application/csv' for csv format;
        
        return $this->_prepareDownloadResponse($_fileName, $_file, $_fileMimeType . '; charset="' . $_fileCharset . '"');
    }

    /****************
     * Label printing
     ****************/

    /**
     * Grid for label mass printing
     */
    public function massLabelPrintingGridAction()
    {
        $this->_title($this->__('Mondial Relay'))->_title($this->__('Labels Printing'));       
        $this->loadLayout()
                ->_setActiveMenu('sales/pointsrelais')
                ->_addContent($this->getLayout()->createBlock('mondialrelay/adminhtml_grid_labelprinting'))
                ->renderLayout();
    }

    /*
     * AJAX mass shipping grid
     */
    public function ajaxMassLabelPrintingGridAction()
    {
        $this->loadLayout('empty');
        
        $this->getLayout()->createBlock('core/text_list', 'root', array('output' => 'toHtml'));
        $_grid = $this->getLayout()
                ->createBlock('mondialrelay/adminhtml_grid_labelprinting_grid')
                ->setTrackingIds($this->getRequest()->getPost('tracking_ids', null));
        $this->getLayout()->getBlock('root')->append($_grid);
        
        $_formkey = $this->getLayout()->createBlock('core/template', 'formkey')->setTemplate('formkey.phtml');
        $this->getLayout()->getBlock('root')->append($_formkey);
        
        $this->getResponse()->setBody($this->getLayout()->getBlock('root')->toHtml());
    }

    /**
     * Mondial Relay mass label printing
     * From Admin > Sales > Mondial Relay > Label Print, Action = label print
     */
    public function massLabelPrintingAction()
    {
        $_trackingIds = $this->getRequest()->getParam('tracking_ids');
        if (count($_trackingIds))
        {
            $_trackings = implode(';', $_trackingIds);
            try
            {
                // We get the pdf file from Mondial Relay web service
                $_urlLabel = Mage::helper('mondialrelay/ws')->getWsLabelUrl($_trackings);
                $this->_processDownload($_urlLabel, Mage_Downloadable_Helper_Download::LINK_TYPE_URL);
            }
            catch (Exception $_e)
            {               
                $this->_getSession()->addError($this->__('An error has occurred during label recovery (%s)',
                        $_e->getMessage()));
                $this->_redirect('*/*/massLabelPrintingGrid');
            }
        }
        
    }

    
    
    /****************
     * Mass returning
     ****************/
    
    /*
     * Refresh mass returning grid
     */
    public function ajaxMassReturningGridAction()
    {
        $this->loadLayout('empty');
        
        $this->getLayout()->createBlock('core/text_list', 'root', array('output' => 'toHtml'));
        $_grid = $this->getLayout()
                ->createBlock('mondialrelay/adminhtml_grid_massreturning_grid')
                ->setShipmentIds($this->getRequest()->getPost('shipment_ids', null));
        $this->getLayout()->getBlock('root')->append($_grid);
        
        $_formkey = $this->getLayout()->createBlock('core/template', 'formkey')->setTemplate('formkey.phtml');
        $this->getLayout()->getBlock('root')->append($_formkey);
        
        $this->getResponse()->setBody($this->getLayout()->getBlock('root')->toHtml());
    }

    
    /*
     * Grid for mass returning
     */
    public function massReturningGridAction()
    {
        $this->_title($this->__('Mondial Relay'))->_title($this->__('Mass Returning'));
        $this->loadLayout()
                ->_setActiveMenu('sales/pointsrelais')
                ->_addContent($this->getLayout()->createBlock('mondialrelay/adminhtml_grid_massreturning'))
                ->renderLayout();
    }

    /*
     * Mondial Relay mass returning (Web Service)
     * From Admin > Sales > Mondial Relay > Mass Returning, Action = mass shipping (web service)
     */
    public function massReturningWsAction()
    {
        $_shipmentIds = (array)$this->getRequest()->getPost('shipment_ids');                
        $_shipmentsNb = count($_shipmentIds);
        $_notify = (bool)$this->getRequest()->getPost('notify');
        
        $_shippingModel = Mage::getSingleton('mondialrelay/shipping');
        $_reverseTracks = array();
        
        foreach($_shipmentIds as $_shipmentId)
        {            
            // To comply with _initShipment that is called from _registerReverseShipment
            $this->getRequest()->setParam('shipment_id', $_shipmentId);
            
            try
            {
                $_shippingModel->setShipment($_shipmentId)->setNotifyCustomer($_notify);
                
                // Looking if a reverse has already be made for this shipment
                $_reverse = false;
                foreach ($_shippingModel->getShipment()->getAllTracks() as $_track)
                {
                    $_reverse = ('reverse' === $_track->getDescription());
                    if ($_reverse)
                    {
                        break;
                    }
                }
                if ($_reverse)
                {
                    $this->_getSession()->addError(
                        $this->__('A return has already been registered for shipment #%s',
                                    $_shippingModel->getShipment()->getIncrementId()));
                    continue;
                }
                        
                $_shippingModel->requestDefaultReturn($_notify);
                $_number = $this->_registerReturn($_shippingModel);
                
                if ($_number)
                {
                    $_reverseTracks[]= $_number;
                }
            }
            catch (Exception $_e)
            {
                $this->_getSession()->addError($this->__('Unable to create return for shipment #%s (%s)', 
                        $_shippingModel->getShipment()->getIncrementId(), $_e->getMessage()));               
            }
            
            // We unregister 'current_shipment' registered by _initShipment 
            Mage::unregister('current_shipment');
        }
        
        // If there are reverse tracks, we manually run returnLabelPrinting action
        // Otherwise we redirect to mass returning grid to display error messages
        if ($_shipmentsNb === count($_reverseTracks))
        {
            $this->getRequest()->setParam('tracking_ids', $_reverseTracks);
            $this->massLabelPrintingAction();
        }
        else
        {
            $this->_redirect('*/*/massReturningGrid');
        }
    }
    
    /*************
     * Re-shipping
     **************/

    /**
     * Create re-shipping label from backend return&reverse form
     */
    public function createReshippingAction()
    {
        $_shipmentId = $this->getRequest()->getParam('shipment_id');
        $_notify = (bool)$this->getRequest()->getPost('notify');
        $_postData = $this->getRequest()->getPost(); 
        
        try
        {
            $_shippingModel = Mage::getSingleton('mondialrelay/shipping');
            $_shippingModel->setShipment($_shipmentId)->setNotifyCustomer($_notify)->requestReshippingFromReverseForm($_postData);
            $this->_registerReshipping($_shippingModel);
        }
        catch (Exception $_e)
        {
                $this->_getSession()->addError($this->__('Unable to create re-shipping for shipment #%s (%s)',
                        $_shipmentId, $_e->getMessage()));               
        }
        
        if ($_shippingModel->getNumber() && isset($_postData['download']) && $_postData['download'])
        {
            $this->_downloadLabel(array($_shippingModel->getNumber()));
        }
        else
        {
            $this->_redirect('adminhtml/sales_order_shipment/view', array('shipment_id' => $_shipmentId));
        }
    }
    
    /**
     *  Register reshipping shipment
     * 
     *  @param Man4x_MondialRelay_Model_Shipping shippingModel
     *  @return false | string (reshipping track id)
     */
    private function _registerReshipping($shippingModel)
    {
        $_shipment = $this->_initShipment(); // ok if shipment_id in request params
        if ($_shipment)
        {
            if ($_number = $shippingModel->getNumber())
            {
                // We add reverse track
                $_track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($_number)
                        ->setCarrierCode($shippingModel->getMethod())
                        ->setTitle($shippingModel->getTitle())
                        ->setDescription('reshipping');
                
                $_shipment->addTrack($_track)->save();
                
                try
                {
                    // Send label link to customer (if asked)
                    $_notification = $shippingModel->notifyCustomer();
                }
                catch (Exception $_e)
                {
                    $this->_getSession()->addNotice($this->__('Email unsuccessfully sent for shipment #%s (%)',
                            $_shipment->getIncrementId(), $_e->getMessage()));
                    $_notification = false;
                }
                
                // We add reverse creation comment
                $_shipment->addComment(
                    $this->__('Creation of a re-shipping label (%s)', 
                                $shippingModel->getMode()), $_notification, false);
                
                $this->_saveShipment($_shipment);
                
                
                // Add success message
                $this->_getSession()->addSuccess($this->__(
                        'Re-shipping successfully created for shipment #%s', $_shipment->getIncrementId()));
                
                return $_track->getId();
            }
        }
        
        return false;
    }

    /*********
     * Returns
     **********/
    
    /**
     * Create return shipping label from backend return&reverse form
     */
    public function createReturnAction()
    {
        $_shipmentId = $this->getRequest()->getParam('shipment_id');
        $_notify = (bool)$this->getRequest()->getPost('notify');
        $_postData = $this->getRequest()->getPost(); 
        
        try
        {
            $_shippingModel = Mage::getSingleton('mondialrelay/shipping');
            $_shippingModel->setShipment($_shipmentId)->setNotifyCustomer($_notify)->requestReturnFromReverseForm($_postData);            
            $this->_registerReturn($_shippingModel);
        }
        catch (Exception $_e)
        {
                $this->_getSession()->addError($this->__('Unable to create return for shipment #%s (%s)',
                        $_shipmentId, $_e->getMessage()));               
        }
        
        if ($_shippingModel->getNumber() && isset($_postData['download']) && $_postData['download'])
        {
            $this->_downloadLabel(array($_shippingModel->getNumber()));
        }
        else
        {
            $this->_redirect('adminhtml/sales_order_shipment/view', array('shipment_id' => $_shipmentId));
        }
    }
    
    /**
     *  Register return shipment
     * 
     *  @param Man4x_MondialRelay_Model_Shipping shippingModel
     *  @return false | string (reverse track number)
     */
    private function _registerReturn($shippingModel)
    {
        $_shipment = $this->_initShipment(); // ok if shipment_id in request params
        if ($_shipment)
        {
            if ($_number = $shippingModel->getNumber())
            {
                // We add reverse track
                $_track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($_number)
                        ->setCarrierCode($shippingModel->getMethod())
                        ->setTitle($shippingModel->getTitle())
                        ->setDescription('reverse');
                
                $_shipment->addTrack($_track)->save();
                
                try
                {
                    // Send label link to customer (if asked)
                    $_notification = $shippingModel->notifyCustomer();
                }
                catch (Exception $_e)
                {
                    $this->_getSession()->addNotice($this->__('Email unsuccessfully sent for shipment #%s (%)',
                            $_shipment->getIncrementId(), $_e->getMessage()));
                    $_notification = false;
                }
                
                // We add reverse creation comment
                $_shipment->addComment(
                    $this->__('Creation of a return shipping label (%s)', 
                                $shippingModel->getMode()), $_notification, false);
                
                $this->_saveShipment($_shipment);
                
                
                // Add success message
                $this->_getSession()->addSuccess($this->__(
                        'Return successfully created for shipment #%s', $_shipment->getIncrementId()));
                
                return $_number;
            }
        }
        
        return false;
    }
        

    /**********************
     * Return label printing
     ***********************/
        
    /**
     * Get shipping label(s) URL
     * AJAX call from [PRINT SHIPPING LABEL] and [PRINT REVERSE LABEL] buttons from admin view shipment 
     */
    public function getShippingLabelUrlAction()
    {
        $_url = Mage::helper('mondialrelay')->__('There is no available shipping label');
        $_shipment = Mage::registry('current_shipment');
        
        if ($_store = $this->getRequest()->getPost('store') && $_numbers = $this->getRequest()->getPost('numbers'))
        {
            try
            {
                // We get the pdf url from Mondial Relay web service
                $_url = Mage::helper('mondialrelay/ws')->getWsLabelUrl((array)$_numbers, $_store);
                if (strlen($_url) < 4)
                {
                    // Error 
                    $_url = Mage::helper('mondialrelay')->__('An error has occurred during label recovery (%s)',
                        Mage::helper('mondialrelay/ws')->convertStatToTxt($_url));
                }
            }
            catch (Mage_Core_Exception $e)
            {
                // Error
                $_url = Mage::helper('mondialrelay')->__('An error has occurred during label recovery (%s)', 
                        implode(',', $e->getMessage()));
            }
        }
        
        echo $_url;
    }
    
    
    /**
     *  Download shipments labels
     * 
     *  @param array tracking
     *  @return 
     */
    private function _downloadLabel($tracking)
    {
        try
        {
            // We get the pdf file from Mondial Relay web service
            $_urlLabel = Mage::helper('mondialrelay/ws')->getWsLabelUrl($tracking);
            $this->_processDownload($_urlLabel, Mage_Downloadable_Helper_Download::LINK_TYPE_URL);
        }
        catch (Exception $_e)
        {               
            $this->_getSession()->addError($this->__('An error has occurred during label recovery (%s)',
                        $_e->getMessage()));
            $this->_redirect('*/*/*');
        }
    }
    
    /***********
     * Action url
     ************/
    
    /**
     * Save pickup data in admin session for an order placed from backend
     */       
    public function savePickupInAdminSessionAction()
    {
        $_pickup = array();
        foreach ($this->getRequest()->getPost() as $_key => $_value)
        {
            $_pickup[$_key] = $_value;
        }
        Mage::getSingleton('adminhtml/session')->setData('selected_pickup', $_pickup);
    }

    /**
     * Download resource from WS server
     * 
     * @param string $resource
     * @param string $resourceType
     * @return array 
     */
    private function _processDownload($resource, $resourceType)
    {
        $_helper = Mage::helper('downloadable/download'); /* @var $helper Mage_Downloadable_Helper_Download */

        $_helper->setResource($resource, $resourceType);

        $_fileName = $_helper->getFilename();
        $_contentType = $_helper->getContentType();

        $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', $_contentType, true);

        if ($_fileSize = $_helper->getFilesize())
        {
            $this->getResponse()->setHeader('Content-Length', $_fileSize);
        }

        $this->getResponse()->clearBody();
        $this->getResponse()->sendHeaders();

        $_helper->output();
    }

}