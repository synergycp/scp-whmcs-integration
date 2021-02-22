<?php

namespace Scp\Whmcs\Server\Inventory;

use Scp\Server\ServerRepository;
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

  const REQUIRED_PRODUCT_FIELDS = [
    WhmcsConfig::MEM_BILLING_ID,
    WhmcsConfig::CPU_BILLING_ID,
    WhmcsConfig::DISK_BILLING_IDS,
    WhmcsConfig::IP_GROUP_BILLING_IDS,
  ];

  public function __construct(Whmcs $whmcs, ServerRepository $servers, Database $database, WhmcsConfig $config) {
    $this->database = $database;
    $this->whmcs = $whmcs;
    $this->servers = $servers;
    $this->config = $config;
  }

  public function sync() {
    if (!$this->shouldSync()) {
      return false;
    }

    
    $this->getProductConfigs()->each(function ($productConfig) {
      $this->syncProductConfig($productConfig);
    });

    return false;
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
      return array_merge(
        ['pid' => $product->id],
        $this->config->configForProduct($product)
      );
    })->filter(function ($productConfig) {
      return $productConfig[WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT];
    });
  }

  private function shouldSync() {
    // TODO: from setting
    return true;
  }
}
