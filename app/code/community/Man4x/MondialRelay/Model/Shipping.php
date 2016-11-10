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
 * @desc        Mondial Relay shipping model
 * @author      Emmanuel Catrysse
 * @license     Emmanuel Catrysse (man4x[@]hotmail[.]fr)
 * 
 * This model is intended to manage Mondial Relay shipments:
 *  - initiated from the back-end reverse form (return and reshipping)
 *  - initiated from the mass returning menu
 *
 */

class Man4x_MondialRelay_Model_Shipping
    extends Mage_Shipping_Model_Shipping
{   
    /**
     * Store address
     */
    const XML_PATH_RETURN_TO_ORIGIN         = 'carriers/mondialrelay/return_to_origin';
    const XML_PATH_RETURN_STORE_RECIPIENT   = 'carriers/mondialrelay/return_recipient';
    const XML_PATH_RETURN_STORE_ADDRESS1    = 'carriers/mondialrelay/return_address1';
    const XML_PATH_RETURN_STORE_ADDRESS2    = 'carriers/mondialrelay/return_address2';
    const XML_PATH_RETURN_STORE_CITY        = 'carriers/mondialrelay/return_city';
    const XML_PATH_RETURN_STORE_ZIP         = 'carriers/mondialrelay/return_postcode';
    const XML_PATH_RETURN_PHONE             = 'carriers/mondialrelay/return_phone';
    const XML_PATH_RETURN_EMAIL             = 'carriers/mondialrelay/return_email';
    
    // We don't include 'return' in country and region attribute name to enable backend region updating logic
    const XML_PATH_RETURN_STORE_COUNTRY_ID  = 'carriers/mondialrelay/country_id';
    const XML_PATH_RETURN_STORE_REGION_ID   = 'carriers/mondialrelay/region_id';

    const XML_PATH_RETURN_EMAIL_TEMPLATE    = 'carriers/mondialrelay/reverse_template';
    
    /*
     * Allowed reverse modes (as defined in Mondial Relay generic settings) for the current store
     */
    private $_allowedReverseModes;
    
    /*
     * Initial order / shipment data
     */
    private $_order = null;
    private $_shipment = null;
    private $_storeId = null;
    private $_shipmentWeight = 0;
    private $_shipmentPackages = 1;
        
    private $_mrMethod = null; // mondialrelaypickup | mondialrelayhome
    private $_mrMode = null; // REL | CDR | CDS
    
    /*
     * Collection address
     *  - for pickup collection, customer's billing address (according to Mondial Relay specification)
     *  - for home collection, customer's shipping address
     */
    private $_collectionAddress = null;
    
    /*
     * Returning address
     *  - for pickup collection, customer's billing address (according to Mondial Relay specification)
     *  - for home collection, customer's shipping address
     */
    private $_returningAddress = null;
            
    /*
     * In case of reverse pickup
     */
    private $_collectionPickupId = '';
    private $_collectionPickupCountry = '';
    private $_collectionPickups = null;
    
    /*
     * Customer notification by email (including reverse shipping label download link)
     */
    private $_notifyCustomer = false;
    
    /*
     * Shipment result data (including tracking ID)
     */
    private $_shipmentResult = null;

    
    /********
     * Getter
     ********/
    
    /**
     * Get shipment
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getShipment()
    {
        return $this->_shipment;
    }

    /**
     * Get shipping method (mondialrelaypickup | mondialrelayhome)
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->_mrMethod;
    }
    
    /**
     * Get shipping mode (REL | CDR | CDS)
     *
     * @return string
     */
    public function getMode()
    {
        return $this->_mrMode;
    }

    /**
     * Get shipping title
     *
     * @return string
     */
    public function getTitle()
    {
        return 'Reverse Mondial Relay (' . $this->_mrMode . ')';
    }

    /**
     * Get customer notification
     *
     * @return string
     */
    public function getNotify()
    {
        return $this->_notifyCustomer;
    }


    /**
     * Get reverse shipment Mondial Relay ID
     *
     * @return false | string
     */
    public function getNumber()
    {
        $_number = is_null($this->_shipmentResult) ? false : $this->_shipmentResult->getNumber();
        return $_number;
    }
    
    /********
     * Setter
     ********/
    
    /**
     * Set initial shipment
     *
     * @param int | Mage_Sales_Model_Order_Shipment shipment
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function setShipment($shipment)
    {
        // We reset reverse shipment to be sure propertyt will correspond to this shipment (and thus be null in case of abortion)
        $this->_shipmentResult = null;
        
        if ($shipment instanceof Mage_Sales_Model_Order_Shipment)
        {
            $this->_shipment = $shipment;
        }
        else
        {
            $this->_shipment = Mage::getModel('sales/order_shipment')->load($shipment);
            if (!$this->_shipment->getId())
            {
                Mage::throwException(Mage::helper('sales')->__('Invalid shipment'));
            }
        }
        
        $this->_shipmentWeight = Mage::helper('mondialrelay')->getItemsWeight($this->_shipment->getAllItems());
        if ($this->_shipment->getPackages())
        {  
            $this->_shipmentPackages = count(unserialize($this->_shipment->getPackages()));
        }
        $this->setOrder($this->_shipment->getOrder());
        
        // We update returning address
        $this->_returningAddress = $this->_getReturningAddress();

        return $this;
    }
            
    /**
     * Set initial order
     *
     * @param int | Mage_Sales_Model_Order order
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function setOrder($order)
    {
        if ($order instanceof Mage_Sales_Model_Order)
        {
            $this->_order = $order;
        }
        else
        {
            $this->_order = Mage::getModel('sales/order')->load($order);
            if (!$this->_order->getId())
            {
                Mage::throwException(Mage::helper('sales')->__('Invalid order'));
            }
        }
        
        // Default collection address is shipping address (except if shipment was pickup shipping, then billing address)
        $this->_collectionAddress = (false !== strpos($this->_order->getShippingMethod(), 'mondialrelaypickup')) ?
                $this->_order->getBillingAddress() :
                $this->_order->getShippingAddress();
        
        if ($this->_storeId !== $this->_order->getStoreId())
        {
            $this->_storeId = $this->_order->getStoreId();
            
            $this->_allowedReverseModes = array();
            $_allowedReverseModes = Mage::helper('mondialrelay')->getAvailableReverseMethods($this->_storeId);
            foreach($_allowedReverseModes as $_mrMode => $_config)
            {
                $this->_allowedReverseModes[$_config['mode']] = $_mrMode;
            }
        }

        return $this;
    }
    
    /**
     * Set notification to customer
     *
     * @param bool notify
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function setNotifyCustomer($notify)
    {
        $this->_notifyCustomer = (bool)$notify;
        return $this;
    }
    
    /*********
     * Returns
     *********/    

    /**
     * Init default return from mass returning 
     *
     * @param bool notify
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function requestDefaultReturn($notify)
    {
        $this->_notifyCustomer = $notify;
        
        $_rm = explode('_', $this->_getDefaultReverseMethod());
        $this->_mrMethod = $_rm[0];
        $this->_mrMode = $_rm[1];
                        
        $this->_requestToReturn();
            
        return $this;           
    }
    
    /**
     * We search for Mondial Relay methods that would have been available for shipment and get the first allowed corresponding
     * default reverse mode
     *
     * @return string
     */
    private function _getDefaultReverseMethod()
    {
        $_reverseShippingMethod = '';
        
        $this->resetResult();
         
        // We use quote address instead of order address to have items 
        $_quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($this->_order->getQuoteId());
        $_quoteAddress = $_quote ? $_quote->getShippingAddress() : null;
        if (!$_quoteAddress)
        {
            Mage::throwException(Mage::helper('mondialrelay')->__(
                        'Unable to retrieve quote address for shipment #%s', $this->_shipment->getIncrementId()));
        }
            
        // We search for Mondial Relay methods that would have been valid for shipment
        $this->collectRatesByAddress($_quoteAddress, array('mondialrelaypickup', 'mondialrelayhome'));
        foreach($this->getResult()->asArray() as $_carrier => $_data)
        {
            foreach(array_keys($_data['methods']) as $_method)
            {
                $_mode = $_carrier . '_' . $_method;
                if (isset($this->_allowedReverseModes[$_mode]))
                {
                    // If reverse method is pickup, we get the first available pickup id
                    if ('mondialrelaypickup' === $_carrier)
                    {
                        try
                        {
                            $_pickups = $this->_getCollectionPickups();
                            $_pickup = reset($_pickups);
                            $this->_collectionPickupId = $_pickup['id'];
                            $this->_collectionPickupCountry = $_pickup['country'];
                        }
                        catch (Exception $_e)
                        {
                            Mage::throwException($_e->getMessage());
                            continue;
                        }
                    }

                    $_reverseShippingMethod = $this->_allowedReverseModes[$_mode];
                    break 2;
                }
            }
        }
        
        if (empty($_reverseShippingMethod))
        {
                Mage::throwException(Mage::helper('mondialrelay')->__(
                        'Unable to get a valid return method for shipment #%s', $this->_shipment->getIncrementId()));            
        }
        
        return $_reverseShippingMethod;
    }

    /**
     * Init return from backend reverse form
     *
     * @param array reverseData
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function requestReturnFromReverseForm($reverseData)
    {
        if (!isset($this->_allowedReverseModes[$reverseData['return-method']]))
        {
            Mage::throwException(Mage::helper('mondialrelay')->__(
                    'Unable to get a valid return method for shipment #%s', $this->_shipment->getIncrementId()));            
            
        }
        $_rm = explode('_', $this->_allowedReverseModes[$reverseData['return-method']]);
        $this->_mrMethod = $_rm[0];
        $this->_mrMode = $_rm[1];
            
        if ('mondialrelaypickup' === $this->_mrMethod)
        {
            $this->_collectionPickupId = $reverseData['pickup-id'];
            $this->_collectionPickupCountry = $reverseData['pickup-country'];
        }
            
        $this->_collectionAddress->setFirstname($reverseData['firstname']);
        $this->_collectionAddress->setLastname($reverseData['lastname']);
        $_streets = array_fill(0, 5, '');
        $_streets[0] = $reverseData['address1'];
        $_streets[1] = $reverseData['address2'];
        $this->_collectionAddress->setStreet($_streets);
        $this->_collectionAddress->setPostcode($reverseData['postcode']);
        $this->_collectionAddress->setCity($reverseData['city']);
        if (isset($reverseData['region_id']))
        {
            $this->_collectionAddress->setRegionId($reverseData['region_id']);
        }
        $this->_collectionAddress->setCountryId($reverseData['country']);
        $this->_collectionAddress->setTelephone($reverseData['phone']);
        $this->_collectionAddress->setEmail($reverseData['email']);
            
        $this->_shipmentWeight = $reverseData['weight'];
        $this->_shipmentPackages = $reverseData['packages'];
            
        $this->_requestToReturn();
            
        return $this;           
    }
    
    /**
     * Prepare and do request to return
     *
     * @return Man4x_MondialRelay_Model_Shipping
     */
    protected function _requestToReturn()
    {
        if (!$this->_order)
        {
            Mage::throwException(Mage::helper('sales')->__('Invalid shipment'));
        }
       
        $_reverseCarrier = $this->getCarrierByCode($this->_mrMethod, $this->_storeId);
        if (!$_reverseCarrier)
        {
            Mage::throwException(
                    Mage::helper('mondialrelay')->__('Invalid return carrier: %s', $this->_mrMethod));
        }
        
        $_request = Mage::getModel('shipping/shipment_request');
        
        // Reverse data
        $_request->setOrderIncrementId($this->_order->getIncrementId());
        $_request->setShippingMode($this->_mrMode); // 24R, LD1...
        $_request->setPackageWeight($this->_shipmentWeight);     
        $_request->setPackages($this->_shipmentPackages);
        $_request->setBaseCurrencyCode(Mage::app()->getStore($this->_storeId)->getBaseCurrencyCode());
        $_request->setStoreId($this->_storeId);
        
        // Sender data
        $_request->setShipperContactPersonName($this->_collectionAddress->getFirstname() . ' ' . $this->_collectionAddress->getLastname());
        $_request->setShipperLastname($this->_collectionAddress->getLastname());
        $_request->setShipperContactPhoneNumber($this->_collectionAddress->getTelephone());
        $_request->setShipperEmail($this->_collectionAddress->getEmail());
        $_request->setShipperStreet1($this->_collectionAddress->getStreet1());
        $_request->setShipperStreet2($this->_collectionAddress->getStreet2());
        $_request->setShipperCity($this->_collectionAddress->getCity());        
        $_request->setShipperPostcode($this->_collectionAddress->getPostcode());        
        $_request->setShipperRegion($this->_collectionAddress->getRegion());
        $_request->setShipperCountryCode($this->_collectionAddress->getCountryId());
        
        // In case of pickup collection
        $_request->setCollectionPickupId($this->_collectionPickupId);
        $_request->setCollectionPickupCountryId($this->_collectionPickupCountry);
        
        // Recipient data
        if (!$this->_returningAddress->getLastname() || !$this->_returningAddress->getCompany()
            || !$this->_returningAddress->getPhone() || !$this->_returningAddress->getEmail()
            || !$this->_returningAddress->getStreet1() || !$this->_returningAddress->getPostcode()
            || !$this->_returningAddress->getCity() || !$this->_returningAddress->getCountryId())
        {
            Mage::throwException(
                Mage::helper('sales')->__('Insufficient information to create shipping label(s). Please verify your Store Information and Shipping Settings.')
            );
        }

        $_request->setRecipientContactPersonName($this->_returningAddress->getFirstname() . ' ' . $this->_returningAddress->getLastname());
        $_request->setRecipientCompany($this->_returningAddress->getCompany());
        $_request->setRecipientContactPhoneNumber($this->_returningAddress->getPhone());
        $_request->setRecipientEmail($this->_returningAddress->getEmail());
        $_request->setRecipientStreet1($this->_returningAddress->getStreet1());
        $_request->setRecipientStreet2($this->_returningAddress->getStreet2());
        $_request->setRecipientCity($this->_returningAddress->getCity());
        $_request->setRecipientPostcode($this->_returningAddress->getPostcode());
        $_request->setRecipientRegionName($this->_returningAddress->getRegion());
        $_request->setRecipientCountryCode($this->_returningAddress->getCountryId());
        
        $this->_shipmentResult = $_reverseCarrier->returnOfShipment($_request);
        
        return $this;
    }
    
    /**
     * Get returning address
     *  - if return to origin is true, use origin address (see Mage_Shipping_Model_Shipping constants)
     *  - if return to origine is false, use returning address (see self constants)
     *
     * @return Varien_Object
     */
    protected function _getReturningAddress()
    {
        if (!$this->_returningAddress)
        {
            $_admin = Mage::getSingleton('admin/session')->getUser();
            $_storeInfo = new Varien_Object(Mage::getStoreConfig('general/store_information', $this->_storeId));
            $_toOrigin = (bool) Mage::getStoreConfig(self::XML_PATH_RETURN_TO_ORIGIN, $this->_storeId);
        
            $_address = Mage::getModel("customer/address");
            $_address->setFirstname($_toOrigin ? $_admin->getFirstname() : '');
            $_address->setLastname($_toOrigin ? $_admin->getLastname() : Mage::getStoreConfig(self::XML_PATH_RETURN_STORE_RECIPIENT, $this->_storeId));
            $_address->setCompany($_storeInfo->getName());
            $_address->setPhone($_toOrigin ? $_storeInfo->getPhone() : Mage::getStoreConfig(self::XML_PATH_RETURN_PHONE, $this->_storeId));
            $_address->setEmail($_toOrigin ? $_admin->getEmail() : Mage::getStoreConfig(self::XML_PATH_RETURN_EMAIL, $this->_storeId));
        
            $_street = $_toOrigin ?
                array(
                    Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS1, $this->_storeId),
                    Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS2, $this->_storeId)
                    ) :
                array(
                    Mage::getStoreConfig(self::XML_PATH_RETURN_STORE_ADDRESS1, $this->_storeId),
                    Mage::getStoreConfig(self::XML_PATH_RETURN_STORE_ADDRESS2, $this->_storeId)                
                );        
            $_address->setStreet($_street);
            $_address->setCity(Mage::getStoreConfig($_toOrigin ? self::XML_PATH_STORE_CITY : self::XML_PATH_RETURN_STORE_CITY, $this->_storeId));        
            $_address->setPostcode(Mage::getStoreConfig($_toOrigin ? self::XML_PATH_STORE_ZIP : self::XML_PATH_RETURN_STORE_ZIP, $this->_storeId));
            $_address->setRegionId(Mage::getStoreConfig($_toOrigin ? self::XML_PATH_STORE_REGION_ID : self::XML_PATH_RETURN_STORE_REGION_ID, $this->_storeId));
            $_address->setCountryId(Mage::getStoreConfig($_toOrigin ? self::XML_PATH_STORE_COUNTRY_ID : self::XML_PATH_RETURN_STORE_COUNTRY_ID, $this->_storeId));
            
            $this->_returningAddress = $_address;
        }
        
        $this->_returningAddress->setAllItems($this->_shipment->getAllItems());
        $this->_returningAddress->setPackageWeight($this->_shipmentWeight);
        $this->_returningAddress->setStoreId($this->_storeId);
        $this->_returningAddress->setWebsiteId(Mage::app()->getStore($this->_storeId)->getWebsiteId());
        
        return $this->_returningAddress;
    }

    
    /************
     * Reshipping
     ************/
    
    /**
     * Init reshipping from backend reshipping form
     *
     * @param array rsData
     * @return Man4x_MondialRelay_Model_Shipping
     */
    public function requestReshippingFromReverseForm($rsData)
    {
        $_rm = explode('_', $rsData['reshipping-method']);
        $this->_mrMethod = $_rm[0];
        $this->_mrMode = $_rm[1];
        
        if (!$this->_order)
        {
            Mage::throwException(Mage::helper('sales')->__('Invalid shipment'));
        }
       
        $_reshippingCarrier = $this->getCarrierByCode($this->_mrMethod, $this->_storeId);
        if (!$_reshippingCarrier)
        {
            Mage::throwException(
                    Mage::helper('mondialrelay')->__('Invalid reshipping carrier: %s', $this->_mrMethod));
        }
        
        // We follow here requestToShipment parent method but overriding with existing reshippingData
        $_admin = Mage::getSingleton('admin/session')->getUser();      
        $_baseCurrencyCode = Mage::app()->getStore($this->_storeId)->getBaseCurrencyCode();
        
        $_shipperRegionCode = Mage::getStoreConfig(self::XML_PATH_STORE_REGION_ID, $this->_storeId);
        if (is_numeric($_shipperRegionCode))
        {
            $_shipperRegionCode = Mage::getModel('directory/region')->load($_shipperRegionCode)->getCode();
        }

        $_originStreet1 = Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS1, $this->_storeId);
        $_originStreet2 = Mage::getStoreConfig(self::XML_PATH_STORE_ADDRESS2, $this->_storeId);
        $_storeInfo = new Varien_Object(Mage::getStoreConfig('general/store_information', $this->_storeId));
        
        if (!$_admin->getFirstname() || !$_admin->getLastname() || !$_storeInfo->getName() || !$_storeInfo->getPhone()
            || !$_originStreet1 || !Mage::getStoreConfig(self::XML_PATH_STORE_CITY, $this->_storeId)
            || !$_shipperRegionCode || !Mage::getStoreConfig(self::XML_PATH_STORE_ZIP, $this->_storeId)
            || !Mage::getStoreConfig(self::XML_PATH_STORE_COUNTRY_ID, $this->_storeId)
        ) {
            Mage::throwException(
                Mage::helper('sales')->__('Insufficient information to create shipping label(s). Please verify your Store Information and Shipping Settings.')
            );
        }

        /** @var $request Mage_Shipping_Model_Shipment_Request */
        $_request = Mage::getModel('shipping/shipment_request');
        $_request->setOrderShipment($this->_shipment);
        $_request->setShipperContactPersonName($_admin->getName());
        $_request->setShipperContactPersonFirstName($_admin->getFirstname());
        $_request->setShipperContactPersonLastName($_admin->getLastname());
        $_request->setShipperContactCompanyName($_storeInfo->getName());
        $_request->setShipperContactPhoneNumber($_storeInfo->getPhone());
        $_request->setShipperEmail($_admin->getEmail());
        $_request->setShipperAddressStreet(trim($_originStreet1 . ' ' . $_originStreet2));
        $_request->setShipperAddressStreet1($_originStreet1);
        $_request->setShipperAddressStreet2($_originStreet2);
        $_request->setShipperAddressCity(Mage::getStoreConfig(self::XML_PATH_STORE_CITY, $this->_storeId));
        $_request->setShipperAddressStateOrProvinceCode($_shipperRegionCode);
        $_request->setShipperAddressPostalCode(Mage::getStoreConfig(self::XML_PATH_STORE_ZIP, $this->_storeId));
        $_request->setShipperAddressCountryCode(Mage::getStoreConfig(self::XML_PATH_STORE_COUNTRY_ID, $this->_storeId));
        $_request->setRecipientContactPersonName(trim($rsData['firstname'] . ' ' . $rsData['lastname']));
        $_request->setRecipientContactPersonFirstName($rsData['firstname']);
        $_request->setRecipientContactPersonLastName($rsData['lastname']);
        $_request->setRecipientContactCompanyName($rsData['address1']);
        $_request->setRecipientContactPhoneNumber($rsData['phone']);
        $_request->setRecipientEmail($rsData['email']);
        $_request->setRecipientAddressStreet(trim($rsData['address1'] . ' ' . $rsData['address2']));
        $_request->setRecipientAddressStreet1($rsData['address2']);
        $_request->setRecipientAddressStreet2('');
        $_request->setRecipientAddressCity($rsData['city']);
        $_request->setRecipientAddressRegionCode('');
        $_request->setRecipientAddressPostalCode($rsData['postcode']);
        $_request->setRecipientAddressCountryCode($rsData['country']);
        $_request->setShippingMethod($this->_mrMode . ('mondialrelaypickup' === $this->_mrMethod ? '_' . $rsData['pickup-id'] : ''));
        $_request->setPackageWeight($rsData['weight']);
        $_request->setPackages($rsData['packages']);
        $_request->setBaseCurrencyCode($_baseCurrencyCode);
        $_request->setStoreId($this->_storeId);
		$_request->setIsReshipping(true);

        $_shipmentResult = $_reshippingCarrier->requestToShipment($_request);
        if ($_shipmentResult->hasInfo())
        {
            $_tracks = $_shipmentResult->getInfo();
            $this->_shipmentResult = new Varien_Object();
            $this->_shipmentResult->setNumber($_tracks[0]['tracking_number']);
        }
                        
        return $this;           
    }

    /**
     * Send return label link by email to customer
     * See Mage_Sales_Model_Order_Shipment->sendEmail()
     * 
     * @return bool
     */
    public function notifyCustomer()
    {
        if (!$this->_notifyCustomer || !$this->getNumber())
        {
            return false;
        }
        
        // Start store emulation process
        $_appEmulation = Mage::getSingleton('core/app_emulation');
        $_initialEnvironmentInfo = $_appEmulation->startEnvironmentEmulation($this->_storeId);

        try
        {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $_paymentBlock = Mage::helper('payment')->getInfoBlock($this->_order->getPayment())->setIsSecureMode(true);
            $_paymentBlock->getMethod()->setStore($this->_storeId);
            $_paymentBlockHtml = $_paymentBlock->toHtml();           
        }
        catch (Exception $_e)
        {
            // Stop store emulation process
            $_appEmulation->stopEnvironmentEmulation($_initialEnvironmentInfo);
            throw $_e;
        }
        // Stop store emulation process
        $_appEmulation->stopEnvironmentEmulation($_initialEnvironmentInfo);

        // Retrieve corresponding email template id and customer name
        $_templateId = Mage::getStoreConfig(self::XML_PATH_RETURN_EMAIL_TEMPLATE, $this->_storeId);
        $_customerName = $this->_order->getCustomerName();

        $_emailInfo = Mage::getModel('core/email_info');
        $_emailInfo->addTo($this->_order->getCustomerEmail(), $_customerName);
        $_mailer = Mage::getModel('core/email_template_mailer');
        $_mailer->addEmailInfo($_emailInfo);

        // Set all required params and send emails
        $_mailer->setSender(Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_IDENTITY, $this->_storeId));
        $_mailer->setStoreId($this->_storeId);
        $_mailer->setTemplateId($_templateId);               
        $_mailer->setTemplateParams(array(
                'order'                 => $this->_order,
                'shipment'              => $this->_shipment,
                'billing'               => $this->_order->getBillingAddress(),
                'payment_html'          => $_paymentBlockHtml,
                'download_link'         => Mage::helper('mondialrelay/ws')->getWsLabelUrl($this->getNumber(), $this->_storeId),
                $this->_mrMethod   => $this->_getCollectionBlockHtml(),
            )
        );
        $_mailer->send();

        return true;      
    }
    
    /*
     * Get collection block HTML for notification email
     *  - for a pickup collection, requested pickup address (if set) or list of available pickups near the requisted address
     *  (since Mondial Relay enables the returner to drop off his parcel in any pickup of his choice)
     *  - for a home collection pickup, home collection address
     * 
     * @return string (HTML)
     */
    public function _getCollectionBlockHtml()
    {        
        if ('mondialrelaypickup' === $this->_mrMethod)
        {
            // We get pickups list given requested postcode / country / parcel weight / store
            try
            {
                $_pickups = $this->_getCollectionPickups();
                
                if (!is_array($_pickups))
                {
                    Mage::throwException(Mage::helper('mondialrelay')->__(
                        'Impossible to get any available pick-up for collection: please contact us.'));
                }

                $_html = '<ul>';
                foreach (array_values($_pickups) as $_p)
                {
                    $_html .= '<li>' . $_p['name'] . ' &bull; ' . $_p['street'] . ' ' . $_p['postcode'] . ' ' . $_p['city'] . '</li>';
                }
                $_html .= '</ul>';
                
            }
            catch (Exception $_e)
            {
                return $_e->getMessage();
            }
        }
        else
        {
            $_html = $this->_collectionAddress->format('html');
        }
        
        return $_html;
    }
    
    /**
     * Get collection pickups
     * If not set, we build it
     *
     * @return array
     */
    private function _getCollectionPickups()
    {
        if (!$this->_collectionPickups && $this->_collectionAddress)
        {
            $this->_collectionPickups = Mage::helper('mondialrelay/ws')->wsGetPickups(
                        $this->_collectionAddress->getPostcode(),
                        $this->_collectionAddress->getCountryId(),
                        $this->_shipmentWeight,
                        $this->_storeId
            );
        }
        
        return $this->_collectionPickups;
    }
}
