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
        $urlAction = sprintf(
            'clientarea.php?action=productdetails&id=%d&modop=custom&a=',
            $this->servers->currentBillingId()
        );

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'url_action' => $urlAction,
                'server' => $server,
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
