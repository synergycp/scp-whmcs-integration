<?php

namespace Scp\Whmcs;

use Scp\Api\ApiTransport as OriginalApiTransport;
use Scp\Api\ApiResponse;
use Scp\Api\JsonDecodingError;
use Scp\Whmcs\LogFactory;

class ApiTransport extends OriginalApiTransport
{
    /**
     * @var LogFactory
     */
    protected $log;

    public function __construct(
        LogFactory $log
    ) {
        $this->log = $log;
    }

    public function call($method, $url, $postData, array $headers)
    {
        $response = parent::call($method, $url, $postData, $headers);

        $this->log($method, $url, $postData, $headers, $response);

        return $response;
    }

    /**
     * Request/response fields whose values must never appear in the module log.
     *
     * @var array
     */
    protected $sensitiveFields = ['key', 'password', 'api_key'];

    protected function log($method, $url, $postData, array $headers, ApiResponse $response)
    {
        $module = 'synergycp'; // TODO: define in Whmcs
        $action = 'create';
        $replace = $this->sensitiveValues($url, $postData);

        if (is_array($postData)) {
            $postData = var_export($postData, true);
        }
        $data = 'HTTP ' . $method . ' ' . $url . "\n"
            . implode("\n", $headers) . "\n\n"
            . $postData;

        $raw = $response->raw();
        $respData = null;

        try {
            $respData = (array) $response->decode();
        } catch (JsonDecodingError $exc) {
        }

        $this->log->call($module, $action, $data, $raw, $respData, $replace);
    }

    /**
     * Collect credential values from the request so WHMCS masks them in the log.
     *
     * @param string       $url
     * @param string|array $postData
     *
     * @return array
     */
    public function sensitiveValues($url, $postData)
    {
        $values = [];

        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            $this->collectSensitive($params, $values);
        }

        if (is_string($postData) && $postData !== '') {
            $postData = json_decode($postData, true);
        }
        if (is_array($postData)) {
            $this->collectSensitive($postData, $values);
        }

        return array_values(array_unique(array_filter($values, 'strlen')));
    }

    /**
     * @param array $data
     * @param array $values
     */
    private function collectSensitive(array $data, array &$values)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->collectSensitive($value, $values);
            } elseif (in_array($key, $this->sensitiveFields, true) && is_scalar($value)) {
                $values[] = (string) $value;
            }
        }
    }
}
