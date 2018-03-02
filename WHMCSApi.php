<?php
/**
 * WHMCS Sample API Call
 *
 * @package    WHMCS
 * @author     WHMCS Limited <development@whmcs.com>
 * @copyright  Copyright (c) WHMCS Limited 2005-2016
 * @license    http://www.whmcs.com/license/ WHMCS Eula
 * @version    $Id$
 * @link       http://www.whmcs.com/
 */

class Service_WHMCS
{

// For WHMCS 7.2 and later, we recommend using an API Authentication Credential pair.
// Learn more at http://docs.whmcs.com/API_Authentication_Credentials
// Prior to WHMCS 7.2, an admin username and md5 hash of the admin password may be used.

public function __construct()
{
    if (!extension_loaded('curl')) {
        throw new Exception('cURL extension is not enabled');
    }
    
}

public function send($options, $whmcsUrl) {
    // Set post values
    $this->postfields = $options;
    
    // Call the API
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_URL, $whmcsUrl . 'includes/api.php');
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($this->postfields));
    $response = curl_exec($this->ch);
    if (curl_error($this->ch)) {
    die('Unable to connect: ' . curl_errno($this->ch) . ' - ' . curl_error($this->ch));
    }
    curl_close($this->ch);
    
    // Decode response
    $jsonData = json_decode($response, true);
    
    // Dump array structure for inspection
    return $jsonData;   
}


}