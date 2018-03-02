<?php
/**
 * BoxBilling
 *
 * LICENSE
 *
 * This source file is subject to the license that is bundled
 * with this package in the file LICENSE.txt
 * It is also available through the world-wide-web at this URL:
 * http://www.boxbilling.com/LICENSE.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@boxbilling.com so we can send you a copy immediately.
 *
 * @copyright Copyright (c) 2010-2012 BoxBilling (http://www.boxbilling.com)
 * @license   http://www.boxbilling.com/LICENSE.txt
 * @version   $Id$
 */
class Service_BoxBilling
{
    /**
     * Api URL
     *
     * @example https://www.boxbilling.com/api
     * @var string
     */
    protected $_api_url     = NULL;
    /**
     * Api Token found in BoxBilling profile page
     *
     * @example e4yny7yjy5u3yhyhepumuqaquva3y4as
     * @var string
     */
    protected $_api_token   = NULL;
    /**
     * Same service can be used to control BoxBilling as client and guest
     * 
     * @example guest
     * @example admin
     * @example client
     * @var string
     */
    protected $_api_role    = 'admin';
    /**
     * Path to cookie to save session for requests.
     * 
     * @var string - path to cookie. Must be writable 
     */
    protected $_cookie    = NULL;
    public function __construct($options)
    {
        if (!extension_loaded('curl')) {
            throw new Exception('cURL extension is not enabled');
        }
        
        if(isset($options['api_url'])) {
            $this->_api_url = $options['api_url'];
        }
        
        if(isset($options['api_role'])) {
            $this->_api_role = $options['api_role'];
        }
        if(isset($options['api_token'])) {
            $this->_api_token = $options['api_token'];
        }
        
        $this->_cookie = sys_get_temp_dir() . 'bbcookie.txt';
    }
    public function __call($method, $arguments)
    {
        $data = array();
        if(isset($arguments[0]) && is_array($arguments[0])) {
            $data = $arguments[0];
        }
        
        $module = substr($method, 0, strpos($method, '_'));
        $m      = substr($method, strpos($method, '_')+1);
        
        $url = $this->_api_url.'/'.$this->_api_role.'/'.$module.'/'.$m;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,               $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH,          CURLAUTH_BASIC) ;
        curl_setopt($ch, CURLOPT_USERPWD,           $this->_api_role.":".$this->_api_token);
        curl_setopt($ch, CURLOPT_COOKIEJAR,         $this->_cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE,        $this->_cookie);
        curl_setopt($ch, CURLOPT_POST,              true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,        http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    true);
        $result = curl_exec($ch);
        
        if($result === false) {
            $e = new Exception(sprintf('Curl Error: "%s"', curl_error($ch)));
            curl_close($ch);
            throw $e;
        }
        
        curl_close($ch);
        $json = json_decode($result, true);
        
        if(!is_array($json)) {
            throw new Exception(sprintf('BoxBilling API: Invalid Response "%s"', $result));
        }
        if(isset($json->error) && !empty($json->error)) {
            throw new Exception(sprintf('BoxBilling API method "%s" returned error: "%s"', $method, $json->error->message), $json->error->code);
        }
        if(!isset($json["result"])) {
            throw new Exception(sprintf('BoxBilling API: Invalid Response "%s"', $result));
        }
        return $json["result"];
    }
}