<?php

namespace Scp\Whmcs\Server\Usage;

use Scp\Api\ApiError;
use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Api;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Database\Database;
use Scp\Support\Collection;

class UsageUpdater
{
    /**
     * @var LogFactory
     */
    protected $log;

    /**
     * @var UsageFormatter
     */
    protected $format;

    /**
     * @var ServerRepository
     */
    protected $servers;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(
        Api $api,
        Database $database,
        LogFactory $log,
        UsageFormatter $format,
        ServerRepository $servers
    ) {
        $this->api = $api;
        $this->log = $log;
        $this->format = $format;
        $this->servers = $servers;
        $this->database = $database;
    }

    /**
     * @return bool
     */
    public function runAndLogErrors()
    {
        try {
            $this->run();

            return true;
        } catch (ApiError $exc) {
            $this->log->activity(
                'SynergyCP: Error running usage update: %s',
                $exc->getMessage()
            );
        }

        return false;
    }

    /**
     * @return bool
     * @throws ApiError
     */
    public function run()
    {
        // Get bandwidth from SynergyCP
        $fail = false;
        $this->servers->query()->where('integration_id', 'me')->chunk(100, function ($servers) use (&$fail) {
            $servers->map(function (Server $server) use (&$fail) {
                try {
                    $this->database
                        ->table('tblhosting')
                        ->where('id', $server->billing->id)
                        ->update($this->prepareUpdates($server));
                } catch (\Exception $exc) {
                    $this->log->activity(
                        'SynergyCP: Usage Update failed: %s',
                        $exc->getMessage()
                    );
                    $fail = true;
                }
            });
        });

        $this->log->activity('SynergyCP: Completed usage update');

        return !$fail;
    }

    /**
     * @param Server $server
     *
     * @return array
     */
    private function prepareUpdates(Server $server)
    {
        $usage = $server->usage;
        return [
            'bwusage' => $usage ? $this->format->bitsToMB($usage->used, 3) : 0,
            'bwlimit' => $usage ? $this->format->bitsToMB($usage->max, 3) : 0,
            'lastupdate' => 'now()',
        ];
    }
}
