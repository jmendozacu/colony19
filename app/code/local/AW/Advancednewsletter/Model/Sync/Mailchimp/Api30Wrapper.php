<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Advancednewsletter
 * @version    2.5.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Advancednewsletter_Model_Sync_Mailchimp_Api30Wrapper
{
    /**
     * Request constants
     */
    const REQUEST_TYPE_GET      = 'get';
    const REQUEST_TYPE_POST     = 'post';
    const REQUEST_TYPE_PUT      = 'put';
    const REQUEST_TYPE_PATCH    = 'patch';
    const REQUEST_TYPE_DELETE   = 'delete';
    const REQUEST_TIMEOUT       = 30;

    /**
     * MailChimp API key
     *
     * @var string
     */
    private $apiKey;

    /**
     * MailChimp API url
     *
     * @var string
     */
    private $apiUrl = 'https://<dc>.api.mailchimp.com/3.0';

    /**
     * @param $apiKey
     * @throws AW_Advancednewsletter_Exception
     */
    public function __construct($apiKey)
    {
        if (strpos($apiKey, '-') === false) {
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('API key is not valid')
            );
        }
        $this->apiKey = $apiKey;
        $pos = strpos($this->apiKey, '-');
        $dataCenter = substr($this->apiKey, $pos + 1);
        $this->apiUrl  = str_replace('<dc>', $dataCenter, $this->apiUrl);
    }

    /**
     * Make GET request
     *
     * @param string $endpoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function get($endpoint, $args = array())
    {
        return $this->runRequest(self::REQUEST_TYPE_GET, $endpoint, $args);
    }

    /**
     * Make POST request
     *
     * @param string $endpoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function post($endpoint, $args = array())
    {
        return $this->runRequest(self::REQUEST_TYPE_POST, $endpoint, $args);
    }

    /**
     * Make PATCH request
     *
     * @param string $endpoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function patch($endpoint, $args = array())
    {
        return $this->runRequest(self::REQUEST_TYPE_PATCH, $endpoint, $args);
    }

    /**
     * Make PUT request
     *
     * @param string $endpoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function put($endpoint, $args = array())
    {
        return $this->runRequest(self::REQUEST_TYPE_PUT, $endpoint, $args);
    }

    /**
     * Make DELETE request
     *
     * @param string $endpoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    public function delete($endpoint, $args = array())
    {
        return $this->runRequest(self::REQUEST_TYPE_DELETE, $endpoint, $args);
    }

    /**
     * Get subscriber hash
     *
     * @param string $email
     * @return string
     * @throws AW_Advancednewsletter_Exception
     */
    public function getSubscriberHash($email)
    {
        return md5(strtolower($email));
    }

    /**
     * Run http request
     *
     * @param string $requestType
     * @param string $endPoint
     * @param array $args
     * @return array
     * @throws AW_Advancednewsletter_Exception
     */
    private function runRequest($requestType, $endPoint, $args = array())
    {
        $url = $this->apiUrl . '/' . $endPoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: apikey ' . $this->apiKey
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        switch ($requestType) {
            case self::REQUEST_TYPE_GET:
                $query = http_build_query($args, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;

            case self::REQUEST_TYPE_POST:
                curl_setopt($ch, CURLOPT_POST, true);
                $jsonEncodedArgs = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonEncodedArgs);
                break;

            case self::REQUEST_TYPE_PATCH:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                $jsonEncodedArgs = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonEncodedArgs);
                break;

            case self::REQUEST_TYPE_PUT:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                $jsonEncodedArgs = json_encode($args);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonEncodedArgs);
                break;

            case self::REQUEST_TYPE_DELETE:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $httpResponse = curl_exec($ch);
        if (!$httpResponse) {
            $curlError = curl_error($ch);
            $curlErrorNo = curl_errno($ch);
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Request failed: %s (%s)', $curlError, $curlErrorNo)
            );
        }
        $httpHeader = curl_getinfo($ch);
        curl_close($ch);

        $response = json_decode($httpResponse, true);

        if ($this->isRequestFailed($httpHeader)) {
            $error =  isset($response['title']) ? $response['title'] : 'Unknown error occurred';
            throw new AW_Advancednewsletter_Exception(
                Mage::helper('advancednewsletter')->__('Request failed: %s (%s)', $error, $httpHeader['http_code'])
            );
        }

        return $response;
    }

    /**
     * Is request failed
     *
     * @param array $httpHeader
     * @return bool
     */
    private function isRequestFailed($httpHeader)
    {
        if (
            isset($httpHeader['http_code']) &&
            $httpHeader['http_code'] >= 200 &&
            $httpHeader['http_code'] <= 299
        ) {
            return false;
        }
        return true;
    }
}
