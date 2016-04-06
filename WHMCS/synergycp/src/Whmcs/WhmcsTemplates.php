<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\ServerService;

class WhmcsTemplates
{
    const CLIENT_AREA = 'ClientArea';

    public function __construct(
        ServerService $servers
    ) {
        $this->servers = $servers;
    }

    public function clientArea()
    {
        $server = $this->servers->current();

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'ips' => $server->entities(),
            ],
        ];
    }

    public static function functions()
    {
        return [
            static::CLIENT_AREA => 'clientArea',
        ];
    }
}
