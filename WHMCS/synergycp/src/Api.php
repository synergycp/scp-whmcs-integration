<?php

namespace Scp\Whmcs;

use Scp\Api\Api as OriginalApi;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Support\Arr;

class Api extends OriginalApi
{
    public function __construct(
        Whmcs $whmcs,
        ApiTransport $transport
    ) {
        $params = $whmcs->getParams();
        $apiKey = Arr::get($params, 'serveraccesshash');
        $hostname = Arr::get($params, 'serverhostname');

        $parsed = parse_url($hostname);
        $path = trim(Arr::get($parsed, 'path', ''), '/');
        if ($path) {
            $path .= '/';
        }

        $host = Arr::get($parsed, 'host', '');
        if ($host) {
            $host .= '/';
        }

        $scheme = Arr::get($parsed, 'scheme', 'http');
        $url = sprintf('%s://%s%s', $scheme, $host, $path);
        
        parent::__construct($url, $apiKey);

        $this->setTransport($transport);
    }
}
