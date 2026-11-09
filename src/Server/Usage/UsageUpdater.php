<?php

namespace Scp\Whmcs\Server\Usage;

use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Whmcs\Api;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Server\Inventory\InventorySynchronizer;

/**
 * Syncs bandwidth usage and server state from SynergyCP into WHMCS.
 *
 * To exercise this manually, run the WHMCS cron task from the WHMCS root:
 *   php crons/cron.php do --UpdateServerUsage -vvv
 */
class UsageUpdater {
  /**
   * @var LogFactory
   */
  private $log;

  /**
   * @var UsageFormatter
   */
  private $format;

  /**
   * @var ServerRepository
   */
  private $servers;

  /**
   * @var Database
   */
  private $database;

  /**
   * @var Api
   */
  private $api;

  /**
   * @var InventorySynchronizer
   */
  private $inventory;

  public function __construct(
    Api $api, Database $database, LogFactory $log, UsageFormatter $format, ServerRepository $servers,
    InventorySynchronizer $inventory
  ) {
    $this->api = $api;
    $this->log = $log;
    $this->format = $format;
    $this->servers = $servers;
    $this->database = $database;
    $this->inventory = $inventory;
  }

  /**
   * @return bool
   */
  public function runAndLogErrors() {
    try {
      $this->run();

      return true;
    } catch (\Exception $exc) {
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
  public function run() {
    $fail = $this->syncBandwidthUsage();
    $fail = $this->inventory->sync() || $fail;

    $this->log->activity('SynergyCP: Completed usage update');

    return !$fail;
  }

  private function syncBandwidthUsage() {
    $fail = false;
    $this->servers->query()->where('integration_id', 'me')->chunk(
      100,
      function ($servers) use (&$fail) {
        $servers->map(
          function (Server $server) use (&$fail) {
            if (!$server->billing) {
              return;
            }
            try {
              $this->updateService($server);
            } catch (\Exception $exc) {
              $this->log->activity(
                'SynergyCP: Usage Update failed: %s',
                $exc->getMessage()
              );
              $fail = true;
            }
          }
        );
      }
    );

    return $fail;
  }

  /**
   * Write bandwidth usage for any live service, but only toggle the status of
   * services WHMCS already considers Active or Suspended. Pending, Cancelled,
   * Fraud and similar statuses are owned by WHMCS and left untouched.
   *
   * @param Server $server
   */
  private function updateService(Server $server) {
    $serviceId = $server->billing->id;

    $this->database
      ->table('tblhosting')
      ->where('id', $serviceId)
      ->whereNotIn('domainstatus', ['Terminated'])
      ->update($this->prepareUsageUpdates($server));

    $this->database
      ->table('tblhosting')
      ->where('id', $serviceId)
      ->whereIn('domainstatus', ['Active', 'Suspended'])
      ->update(['domainstatus' => $this->statusFor($server)]);
  }

  /**
   * @param Server $server
   *
   * @return array
   */
  private function prepareUsageUpdates(Server $server) {
    $usage = $server->usage;

    return [
      'bwusage' => $usage ? $this->format->bitsToMB($usage->used, 3) : 0,
      'bwlimit' => $usage ? $this->format->bitsToMB($usage->max, 3) : 0,
    ];
  }

  /**
   * @param Server $server
   *
   * @return string
   */
  private function statusFor(Server $server) {
    $access = $server->access;

    return $access && $access->is_active ? 'Active' : 'Suspended';
  }
}
