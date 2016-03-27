<?php

namespace Scp\Whmcs\Server\Usage;
use Scp\Api\ApiError;
use Scp\Server\Server;
use Scp\Server\ServerQuery;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Server\Usage\UsageFormatter;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Database\Database;

class UsageUpdater
{
    /**
     * @var LogFactory
     */
    protected $log;

    /**
     * @var ServerRepository
     */
    protected $servers;

    /**
     * @var UsageFormatter
     */
    protected $format;

    /**
     * @var Database
     */
    protected $database;

    public function __construct(
        Database $database,
        LogFactory $log,
        UsageFormatter $format,
        ServerRepository $servers
    ) {
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

            return false;
        }
    }

    /**
     * @return bool
     */
    public function run()
    {
        // Get bandwidth from SynergyCP
        $this->servers->query()->each(function (Server $server) {
            if (!$server->billing_id) {
                return;
            }

            $this->log->activity(
                'SynergyCP: Updating billing ID %s',
                $server->billing_id
            );

            $updates = $this->prepareUpdates($server);

            $this->database->update("tblhosting", $updates, [
                "id" => $server->billing_id,
            ]);
        });

        $this->log->activity('SynergyCP: Completed usage update');

        return true;
    }

    private function prepareUpdates(Server $server)
    {
        return [
            //"diskused" => $values['diskusage'],
            //"dislimit" => $values['disklimit'],
            "bwusage" => $this->format->bitsToMB($server->bandwidth_used),
            "bwlimit" => $this->format->bitsToMB($server->bandwidth_limit, 3),
            "lastupdate" => "now()",
        ];
    }
}
