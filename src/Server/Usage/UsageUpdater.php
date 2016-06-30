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
     * @param string $billingId
     *
     * @return bool
     */
    public function runAndLogErrors($billingId)
    {
        try {
            $this->run($billingId);

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
     */
    public function run($billingId)
    {
        $this->log->activity(
            'SynergyCP: Updating billing ID %s',
            $billingId
        );

        // Get bandwidth from SynergyCP
        $server = $this->servers->findByBillingId($billingId);
        if (!$server) {
            throw new ApiError(sprintf(
                'Server with billing ID: %s not found on Synergy',
                $billingId
            ));
        }

        $updates = $this->prepareUpdates($server);

        try {
            $this->database->table('tblhosting')
                ->where('id', $billingId)
                ->update($updates);
        } catch (\Exception $exc) {
            $this->log->activity(
                'SynergyCP: Usage Update failed: %s',
                $exc->getMessage()
            );

            return false;
        }

        $this->log->activity('SynergyCP: Completed usage update');

        return true;
    }

    private function prepareUpdates(Server $server)
    {
        $bandwidth = $this->getBandwidth($server);

        return [
            //"diskused" => $values['diskusage'],
            //"dislimit" => $values['disklimit'],
            'bwusage' => $this->format->bitsToMB($bandwidth->used, 3) * 8,
            'bwlimit' => $this->format->bitsToMB($bandwidth->limit, 3) * 8,
            'lastupdate' => 'now()',
        ];
    }

    /**
     * Get bandwidth usage data for the given Server.
     *
     * @param  Server $server
     *
     * @return stdClass
     */
    private function getBandwidth(Server $server)
    {
        $url = sprintf(
            'server/%d/port',
            $server->id
        );
        $data = [
            'is_billable' => true,
        ];

        $ports = $this->api->get($url, $data)->data()->data;

        $result = new \stdClass;
        $result->used = 0;
        $result->limit = 0;

        foreach ($ports as $port) {
            $portUrl = sprintf(
                '%s/%d/bandwidth/usage',
                $url,
                $port->id
            );

            $bandwidth = $this->api->get($portUrl)->data();

            $result->used += $bandwidth->used;
            $result->limit += $bandwidth->max;
        }

        return $result;
    }
}
