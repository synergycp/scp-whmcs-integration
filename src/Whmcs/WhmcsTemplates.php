<?php

namespace Scp\Whmcs\Whmcs;
use Scp\Whmcs\Server\ServerService;
use Scp\Whmcs\Server\Usage\UsageFormatter;
use Scp\Whmcs\Client\ClientService;
use Scp\Whmcs\Api;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\Whmcs\WhmcsConfig;
use Scp\Api\ApiSingleSignOn;

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
     * @var WhmcsConfig
     */
    protected $config;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @param Api                       $api
     * @param ClientService             $client
     * @param ServerService             $server
     * @param UsageFormatter            $format
     * @param WhmcsConfig               $config
     * @param Database                  $database
     */
    public function __construct(
        Api $api,
        ClientService $client,
        ServerService $server,
        UsageFormatter $format,
        WhmcsConfig $config,
        Database $database
    ) {
        $this->api = $api;
        $this->client = $client;
        $this->server = $server;
        $this->format = $format;
        $this->config = $config;
        $this->database = $database;
    }

    public function clientArea()
    {
        if (!$server = $this->server->current()) {
            return;
        }

        $server = $server->full();
        $billingId = $this->server->currentBillingId();
        $urlAction = sprintf(
            'clientarea.php?action=productdetails&id=%d&modop=custom&a=',
            $billingId
        );
        $apiKey = $this->client->apiKey();
        $urlApi = $this->api->baseUrl();
        $password = $this->generatePassword(10);
        $manage = $this->config->option(WhmcsConfig::CLIENT_MANAGE_BUTTON);
        $embed = $this->config->option(WhmcsConfig::CLIENT_EMBEDDED_SERVER_MANAGE);

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
                'manage' => $manage,
                'embed' => $embed,
                'embedUrl' => $this->getEmbeddedServerManagerUrl(),
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

    /**
     * @return string
     *
     */
    public function getEmbeddedServerManagerUrl()
    {
        $apiKey = $this->client->apiKey();
        $server = $this->server->currentOrFail();
        $sso = new ApiSingleSignOn($apiKey);
        if ($server) {
            $sso->view($server);
        }
        return $sso->embeddedUrl();
    }

    public static function functions()
    {
        return [
            static::CLIENT_AREA => 'clientArea',
        ];
    }
}
