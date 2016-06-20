<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\Server\Usage\UsageFormatter;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;
use Scp\Whmcs\Database\Database;

class WhmcsTemplates
{
    const CLIENT_AREA = 'ClientArea';

    const ALPHA_NUM = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ClientService
     */
    protected $client;

    /**
     * @var ServerService
     */
    protected $server;

    /**
     * @var UsageFormatter
     */
    protected $format;

    /**
     * @var Database
     */
    protected $database;

    public function __construct(
        Api $api,
        Database $database,
        ClientService $client,
        ServerService $server,
        UsageFormatter $format
    ) {
        $this->api = $api;
        $this->client = $client;
        $this->server = $server;
        $this->format = $format;
        $this->database = $database;
    }

    public function clientArea()
    {
        $server = $this->server->current();
        $billingId = $this->server->currentBillingId();

        $urlAction = sprintf(
            'clientarea.php?action=productdetails&id=%d&modop=custom&a=',
            $billingId
        );

        $apiKey = $this->client->apiKey();
        $urlApi = $this->api->baseUrl();
        $password = $this->generatePassword(10);

        return [
            'templatefile' => 'clientarea',
            'vars' => [
                'password' => $password,
                'url_action' => $urlAction,
                'server' => $server,
                'ips' => $server->entities(),
                'MODULE_FOLDER' => '/modules/servers/synergycp',
                'apiKey' => $apiKey->key,
                'apiUrl' => $urlApi,
                'bandwidth' => $this->clientAreaBandwidth(),
            ],
        ];
    }

    private function clientAreaBandwidth()
    {
        $billingId = $this->server->currentBillingId();
        $hosting = $this->database->table('tblhosting')
            ->where('id', $billingId)
            ->first();

        $used = $hosting->bwusage;
        $limit = $hosting->bwlimit;

        $dispUsed = $this->format->megaBytesToHuman($used);
        $dispPct = $limit ? round(100 * $used / $limit, 2) : 0;
        $dispPct = min($dispPct, 100);
        $dispLimit = $limit ? $this->format->megaBytesToHuman($limit) : null;

        return [
            'used' => $dispUsed,
            'percent' => $dispPct,
            'limit' => $dispLimit,
        ];
    }

    private function generatePassword($length, $characters = self::ALPHA_NUM)
    {
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function functions()
    {
        return [
            static::CLIENT_AREA => 'clientArea',
        ];
    }
}
