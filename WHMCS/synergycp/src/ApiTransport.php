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

    protected function log($method, $url, $postData, array $headers, ApiResponse $response)
    {
        $module = 'synergycp'; // TODO: define in Whmcs
        $action = 'create';
        if (is_array($postData)) {
            $postData = var_export($postData, true);
        }
        $data = 'HTTP ' . $method . ' ' . $url . "\n"
            . implode($headers, "\n") . "\n\n"
            . $postData;

        $raw = $response->raw();
        $respData = null;

        try {
            $respData = (array) $response->decode();
        } catch (JsonDecodingError $exc) {
        }

        $replace = [];

        $this->log->call($module, $action, $data, $raw, $respData, $replace);
    }
}
