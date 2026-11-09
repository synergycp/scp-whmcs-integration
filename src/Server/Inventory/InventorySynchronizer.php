<?php

namespace Scp\Whmcs\Server\Inventory;

use Scp\Server\ServerRepository;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;

class InventorySynchronizer {
  /**
   * @var Whmcs
   */
  private $whmcs;

  /**
   * @var ServerRepository
   */
  private $servers;

  /**
   * @var Database
   */
  private $database;

  /**
   * @var WhmcsConfig
   */
  private $config;

  /**
   * @var LogFactory
   */
  private $log;

  const REQUIRED_PRODUCT_FIELDS = [
    WhmcsConfig::MEM_BILLING_ID,
    WhmcsConfig::CPU_BILLING_ID,
    WhmcsConfig::DISK_BILLING_IDS,
    WhmcsConfig::IP_GROUP_BILLING_IDS,
  ];

  public function __construct(
    Whmcs $whmcs, ServerRepository $servers, Database $database, WhmcsConfig $config, LogFactory $log
  ) {
    $this->database = $database;
    $this->whmcs = $whmcs;
    $this->servers = $servers;
    $this->config = $config;
    $this->log = $log;
  }

  /**
   * @return bool true if any product failed to sync
   */
  public function sync() {
    if (!$this->shouldSync()) {
      return false;
    }

    $fail = false;
    $this->getProductConfigs()->each(function ($productConfig) use (&$fail) {
      try {
        $this->syncProductConfig($productConfig);
      } catch (\Exception $exc) {
        $this->log->activity(
          'SynergyCP: Inventory sync failed for product ID %s: %s',
          $productConfig['pid'],
          $exc->getMessage()
        );
        $fail = true;
      }
    });

    return $fail;
  }

  private function syncProductConfig($productConfig) {
    $filters = $this->getAPIFiltersForProductConfig($productConfig);
    if ($filters === null) {
      return true;
    }
    $countInInventory = $this->servers
      ->query()
      ->where($filters)
      ->where([
        'available' => true,
        'parts' => ['exact' => true],
      ])
      ->forceUncachedTotalCount()
      ->totalCount();
    return $this->database->table('tblproducts')->where('id', $productConfig['pid'])->update(['qty' => $countInInventory]);
  }

  private function getAPIFiltersForProductConfig($productConfig) {
    foreach (self::REQUIRED_PRODUCT_FIELDS as $field) {
      if (!$productConfig[$field]) {
        return null;
      }
    }

    return [
      'mem_billing' => $productConfig[WhmcsConfig::MEM_BILLING_ID],
      'cpu_billing' => $productConfig[WhmcsConfig::CPU_BILLING_ID],
      'disks_billing' => $productConfig[WhmcsConfig::DISK_BILLING_IDS],
      'addons_billing' => $productConfig[WhmcsConfig::ADDON_BILLING_IDS],
      'ip_group_billing' => $productConfig[WhmcsConfig::IP_GROUP_BILLING_IDS],
    ];
  }

  private function getProductConfigs() {
    return $this->database->table('tblproducts')->where('servertype', 'synergycp')->get()->map(function ($product) {
      try {
        return array_merge(
          ['pid' => $product->id],
          $this->config->configForProduct($product)
        );
      } catch (\Exception $exc) {
        $this->log->activity(
          'SynergyCP: Skipping inventory sync for product ID %s: %s',
          $product->id,
          $exc->getMessage()
        );

        return null;
      }
    })->filter(function ($productConfig) {
      return $productConfig && !empty($productConfig[WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT]);
    });
  }

  private function shouldSync() {
    // TODO: from setting
    return true;
  }
}
