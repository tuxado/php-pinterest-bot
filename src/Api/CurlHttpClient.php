<?php

namespace seregazhuk\PinterestBot\Api;

use seregazhuk\PinterestBot\Helpers\Cookies;
use seregazhuk\PinterestBot\Helpers\UrlBuilder;
use seregazhuk\PinterestBot\Api\Contracts\HttpClient;
use function seregazhuk\PinterestBot\url_slug;

/**
 * Class CurlHttpClient.
 */
class CurlHttpClient implements HttpClient
{
    
    const VERBOSE_PREFIX = 'verbose-php-pinterest-bot';
    const VERBOSE_FILE_EXTENSION = 'txt';
   
    /**
     * Custom CURL options for requests.
     *
     * @var array
     */
    protected $options = [
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.98 Safari/537.36'
    ];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * Contains the curl instance.
     *
     * @var resource
     */
    protected $curl;

    /**
     * Path to cookies file
     * @var string
     */
    protected $cookieJar;

    /**
     * Cookies container
     *
     * @var Cookies
     */
    protected $cookies;

    /**
     * @var string
     */
    protected $currentUrl;

    /**
     * Path to directory to store cookie file
     * @var string
     */
    protected $cookiesPath;

    public function __construct(Cookies $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * Load cookies for a specified username. If a username is empty
     * we use a common file for all the anonymous requests.
     *
     * @param string $username
     * @return HttpClient
     */
    public function loadCookies($username = '')
    {
        return $this
            ->initCookieJar($username)
            ->fillCookies();
    }

    /**
     * Executes curl request.
     *
     * @param string $url
     * @param string $postString
     * @param array $headers
     * @return string
     */
    public function execute($url, $postString = '', array $headers = [])
    {
        return $this
            ->init($url, $postString, $headers)
            ->callCurl();
    }

    /**
     * @return mixed
     */
    protected function callCurl()
    {
        $result = curl_exec($this->curl);

        $this->currentUrl = curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);

        curl_close($this->curl);

        $this->fillCookies();

        return $result;
    }

    /**
     * Initializes curl resource with options.
     *
     * @param string $url
     * @param string $postString
     * @param array $headers
     * @return $this
     */
    protected function init($url, $postString, $headers)
    {
        $this->headers = $headers;

        $this->curl = curl_init($url);

        if (empty($this->cookieJar)) {
            $this->loadCookies();
        }

        curl_setopt_array($this->curl, $this->makeHttpOptions($postString));
        
        /*
        curl_setopt($this->curl, CURLOPT_VERBOSE, true);

        $verbose = fopen($this->getVerbosePath($url), 'a+');
        
        curl_setopt($this->curl, CURLOPT_STDERR, $verbose);
        */
            
        return $this;
    }

    /**
     * @return array
     */
    protected function getDefaultHttpOptions()
    {
        
        return [
            CURLOPT_REFERER        => UrlBuilder::URL_BASE,
            CURLOPT_ENCODING       => 'gzip,deflate,br',
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_HTTPHEADER     => $this->headers,
            CURLOPT_COOKIEFILE     => $this->cookieJar,
            CURLOPT_COOKIEJAR      => $this->cookieJar,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        
    }
    
     /**
     * @param string $username
     * @return string
     */
    protected function getVerbosePath($url = '')
    {
        $date   = new \DateTime(); //this returns the current date time
        $date = $date->format('Y-m-d-H-i-s');
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::VERBOSE_PREFIX . '-' . $date . '-' . url_slug($url) . '.' . self::VERBOSE_FILE_EXTENSION;
    }

    /**
     * Adds necessary curl options for query.
     *
     * @param string $postString POST query string
     *
     * @return array
     */
    protected function makeHttpOptions($postString = '')
    {
        // Union custom Curl options and default ones.
        $options = array_replace(
            $this->options,
            $this->getDefaultHttpOptions()
        );

        if (!empty($postString)) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postString;
        }

        return $options;
    }

    /**
     * Set custom Curl options to override default
     *
     * @codeCoverageIgnore
     * @param array $options
     * @return HttpClient
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get a cookie value by name
     *
     * @param $name
     * @return mixed
     */
    public function cookie($name)
    {
        return $this->cookies->get($name);
    }

    /**
     * Get all cookies
     *
     * @return array
     */
    public function cookies()
    {
        return $this->cookies->all();
    }

    /**
     * Set directory to store all cookie files.
     *
     * @codeCoverageIgnore
     * @param string $path
     * @return $this
     */
    public function setCookiesPath($path)
    {
        $this->cookiesPath = $path;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeCookies()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }

        $this->cookieJar = null;

        return $this;
    }

    /**
     * @param $username
     * @return $this
     */
    protected function initCookieJar($username)
    {
        $this->cookieJar = $this->initCookieFile($username);

        return $this;
    }

    /**
     * Returns cookie file name according to the provided username.
     *
     * @param string $username
     * @return string
     */
    protected function initCookieFile($username)
    {
        $cookieName = self::COOKIE_PREFIX . $username;
        $cookieFilePath = $this->getCookiesPath() . DIRECTORY_SEPARATOR . $cookieName;

        if (!file_exists($cookieFilePath)) {
            touch($cookieFilePath);
        }

        return $cookieFilePath;
    }

    /**
     * @return string
     */
    public function getCookiesPath()
    {
        return $this->cookiesPath ? : sys_get_temp_dir();
    }

    /**
     * @return $this
     */
    protected function fillCookies()
    {
        if (!empty($this->cookieJar)) {
            $this->cookies->fill($this->cookieJar);
        }

        return $this;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->currentUrl;
    }

    /**
     * @param string $host '192.168.1.1'
     * @param string $port '12345'
     * @param string $auth Authentication string: 'username:password'
     * @param string $type HTTP|SOCKS
     * @return HttpClient
     */
    public function useProxy($host, $port, $auth = null, $type = null)
    {
        $proxy = [
            CURLOPT_PROXY     => $host,
            CURLOPT_PROXYPORT => $port,
            CURLOPT_PROXYTYPE => $type ?: CURLPROXY_HTTP,
        ];

        if (null !== $auth) {
            $proxy[CURLOPT_PROXYUSERPWD] = $auth;
        }

        return $this->setOptions($proxy);
    }

    public function dontUseProxy()
    {
        unset(
            $this->options[CURLOPT_PROXY],
            $this->options[CURLOPT_PROXYPORT],
            $this->options[CURLOPT_PROXYTYPE],
            $this->options[CURLOPT_PROXYUSERPWD]
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function usesProxy()
    {
        return isset($this->options[CURLOPT_PROXY]);
    }

    /**
     * @codeCoverageIgnore
     * @param string $host
     * @param string $port
     * @param null $auth
     * @return HttpClient
     */
    public function useSocksProxy($host, $port, $auth = null)
    {
        return $this->useProxy($host, $port, CURLPROXY_SOCKS5, $auth);
    }
}
