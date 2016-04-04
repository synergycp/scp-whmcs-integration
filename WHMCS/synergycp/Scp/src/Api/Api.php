<?php

namespace Scp\Api;

/**
 * Class Responsibilities:
 *  - Encoding HTTP request data for the Synergy API
 *  - Sending HTTP requests
 *  - Storing the output of HTTP responses in ApiResponse
 */
class Api
{
    /**
     * @var Api
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $apiKey;

    public function __construct($url, $apiKey)
    {
        $this->url = rtrim($url, '/');
        $this->apiKey = $apiKey;
        $this->transport = new ApiTransport;

        static::instance($this);
    }

    /**
     * Set the API's request transporter.
     * This can also be used to, for instance, log all API requests.
     *
     * @param ApiTransporter $transporter
     *
     * @return $this
     */
    public function setTransport(ApiTransporter $transporter)
    {
        $this->transport = $transporter;

        return $this;
    }

    /**
     * @param  string $method
     * @param  string $path
     * @param  array [$data]  optional
     *
     * @return ApiResponse
     *
     * @throws ApiError
     */
    public function call($method, $path, array $data = [])
    {
        $headers = ['Content-Type: application/json'];
        $getData = $data;
        $postData = "";

        if (strtoupper($method) != 'GET') {
            $getData = [];
            $postData = json_encode($data);
        }

        $url = $this->url($path, $getData);

        return $this->transport->call($method, $url, $postData, $headers);
    }

    /**
     * @param  string $path
     * @param  array [$data]  optional
     *
     * @return ApiResponse
     *
     * @throws ApiError
     */
    public function get($path, array $data = [])
    {
        return $this->call('GET', $path, $data);
    }

    /**
     * @param  string $path
     * @param  array [$data]  optional
     *
     * @return ApiResponse
     *
     * @throws ApiError
     */
    public function post($path, array $data = [])
    {
        return $this->call('POST', $path, $data);
    }

    /**
     * @param  string $path
     * @param  array [$data]  optional
     *
     * @return ApiResponse
     *
     * @throws ApiError
     */
    public function patch($path, array $data = [])
    {
        return $this->call('PATCH', $path, $data);
    }

    /**
    * @param  string $path
    * @param  array [$data]  optional
    *
    * @return ApiResponse
    *
    * @throws ApiError
    */
    public function delete($path, array $data = [])
    {
        return $this->call('DELETE', $path, $data);
    }

    public function url($path = '', array $data = [])
    {
        $data += [
            'key' => $this->apiKey,
        ];

        $path = rtrim($path, '/');

        return "$this->url/$path?" . http_build_query($data);
    }

    /**
     * @param  static [$instance] set the instance
     *
     * @return static
     */
    public static function instance($instance = null)
    {
        static::$instance = $instance ?: static::$instance;

        if (!static::$instance) {
            throw new \RuntimeException("API instance requested but none found.");
        }

        return static::$instance;
    }
}
