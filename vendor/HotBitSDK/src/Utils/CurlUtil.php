<?php

namespace HotBitSDK\Utils;

/**
 * A basic CURL wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package curl
 * @author Sean Huber <shuber@huberry.com>
 **/
class CurlUtil
{

    /**
     * The file to read and write cookies to for requests
     *
     * @var string
     **/
    public $cookie_file;

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
     **/
    public $follow_redirects = true;

    /**
     * An associative array of headers to send along with requests
     *
     * @var array
     **/
    public $headers = array();

    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
     **/
    public $options = array();

    /**
     * The referer header to send along with requests
     *
     * @var string
     **/
    public $referer;

    /**
     * The user agent to send along with requests
     *
     * @var string
     **/
    public $user_agent;

    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string
     * @access protected
     **/
    protected $error = '';

    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
     **/
    public $request;


    /**
     * Initializes a Curl object
     *
     * Sets the $cookie_file to "curl_cookie.txt" in the current directory
     * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)' otherwise
     **/
    function __construct()
    {
        $this->cookie_file = "";//dirname(__FILE__).DIRECTORY_SEPARATOR.'curl_cookie.txt';
        $this->user_agent = "";//isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)';
    }

    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
     **/
    function error()
    {
        return $this->error;
    }


    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     **/
    function request($method, $url, $vars = array(), $timeout = 30)
    {
        $this->error = '';
        $this->request = curl_init();
        if (is_array($vars)) $vars = http_build_query($vars, '', '&');
        $this->http_info['curl_url'] = $url;

        $this->set_request_method($method, $timeout);
        $this->set_request_options($url,$method, $vars);
        $this->set_request_headers();

        $this->begin();
        $response = curl_exec($this->request);
        if (!$response) {
            $this->error = curl_errno($this->request) . ' - ' . curl_error($this->request);
        }

        curl_close($this->request);
        $this->end();
        return $response;
    }

    private $begin_time = 0;
    public $http_info = [];

    private function begin()
    {
        $this->begin_time = microtime(true);
    }

    private function end()
    {
        $resp_time = microtime(true) - $this->begin_time;
        if ($this->error) {
            $this->http_info['exec_time'] = $resp_time;
            $this->http_info['error'] = $this->error;
        } else {
            $this->http_info['exec_time'] = $resp_time;
        }
    }

    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
     **/
    public function set_request_headers()
    {
        $headers = array(
            "Content-Type:  application/x-www-form-urlencoded"
        );
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
     **/
    public function set_request_method($method, $timeout)
    {
        switch (strtoupper($method)) {
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @param string $method
     * @return void
     * @access protected
     **/
    public function set_request_options($url,$method, $vars)
    {
        if (!empty($vars) && $method !== 'GET') {curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);}
        # alter query to get params
        elseif(!empty($vars)){
            $url .= '?'.$vars;
        }

        curl_setopt($this->request, CURLOPT_URL, $url);

        # Set some default CURL options
//        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);

        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, false);

//        if ($this->cookie_file) {
//            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
//            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
//        }
        if ($this->follow_redirects) curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
//        if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }
}
