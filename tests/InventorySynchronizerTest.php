<?php

use Scp\Api\ApiError;
use Scp\Api\ApiQuery;
use Scp\Server\ServerRepository;
use Scp\Support\Collection;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\LogFactory;
use Scp\Whmcs\Server\Inventory\InventorySynchronizer;
use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;

class InventorySynchronizerTest extends TestCase
{
    public function setUp(): void
    {
        $this->sync = new InventorySynchronizer(
            $this->whmcs = Mockery::mock(Whmcs::class),
            $this->servers = Mockery::mock(ServerRepository::class),
            $this->database = Mockery::mock(Database::class),
            $this->config = Mockery::mock(WhmcsConfig::class),
            $this->log = Mockery::mock(LogFactory::class)
        );

        $this->products = Mockery::mock('Illuminate\Database\Query\Builder');
        $this->database->shouldReceive('table')->with('tblproducts')->andReturn($this->products);
        $this->products->shouldReceive('where')->with('servertype', 'synergycp')->andReturnSelf();
    }


    public function testMisconfiguredProductIsSkippedAndOthersStillSync()
    {
        $good = (object) ['id' => 1];
        $bad = (object) ['id' => 2];
        $this->products->shouldReceive('get')->andReturn(new Collection([$good, $bad]));

        $this->config->shouldReceive('configForProduct')
            ->with(Mockery::on(function ($product) { return $product->id === 1; }))
            ->andReturn($this->productConfig());
        $this->config->shouldReceive('configForProduct')
            ->with(Mockery::on(function ($product) { return $product->id === 2; }))
            ->andThrow(new \Exception('Invalid value for Delete Action: bogus'));

        $this->log->shouldReceive('activity')
            ->once()
            ->with('SynergyCP: Skipping inventory sync for product ID %s: %s', 2, 'Invalid value for Delete Action: bogus');

        $this->expectInventoryCount(3);
        $this->products->shouldReceive('where')->with('id', 1)->andReturnSelf();
        $this->products->shouldReceive('update')->once()->with(['qty' => 3])->andReturn(1);

        $this->assertFalse($this->sync->sync());
    }

    public function testApiFailureForOneProductIsLoggedAndReported()
    {
        $this->products->shouldReceive('get')->andReturn(new Collection([(object) ['id' => 7]]));
        $this->config->shouldReceive('configForProduct')->andReturn($this->productConfig());

        $query = Mockery::mock(ApiQuery::class);
        $this->servers->shouldReceive('query')->andReturn($query);
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('forceUncachedTotalCount')->andReturnSelf();
        $query->shouldReceive('totalCount')->andThrow(new ApiError('connection timed out'));

        $this->log->shouldReceive('activity')
            ->once()
            ->with('SynergyCP: Inventory sync failed for product ID %s: %s', 7, 'connection timed out');
        $this->products->shouldReceive('update')->never();

        $this->assertTrue($this->sync->sync());
    }

    public function testProductsWithoutSyncEnabledAreIgnored()
    {
        $this->products->shouldReceive('get')->andReturn(new Collection([(object) ['id' => 1]]));
        $this->config->shouldReceive('configForProduct')->andReturn(
            $this->productConfig([WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT => null])
        );
        $this->servers->shouldReceive('query')->never();
        $this->products->shouldReceive('update')->never();

        $this->assertFalse($this->sync->sync());
    }

    private function expectInventoryCount($count)
    {
        $query = Mockery::mock(ApiQuery::class);
        $this->servers->shouldReceive('query')->andReturn($query);
        $query->shouldReceive('where')
            ->with([
                'mem_billing' => ['mem-1'],
                'cpu_billing' => ['cpu-1'],
                'disks_billing' => ['disk-1'],
                'addons_billing' => null,
                'ip_group_billing' => ['la-1'],
            ])
            ->andReturnSelf();
        $query->shouldReceive('where')
            ->with(['available' => true, 'parts' => ['exact' => true]])
            ->andReturnSelf();
        $query->shouldReceive('forceUncachedTotalCount')->andReturnSelf();
        $query->shouldReceive('totalCount')->andReturn($count);
    }

    private function productConfig(array $overrides = [])
    {
        $config = array_fill(0, 16, null);
        $config[WhmcsConfig::MEM_BILLING_ID] = ['mem-1'];
        $config[WhmcsConfig::CPU_BILLING_ID] = ['cpu-1'];
        $config[WhmcsConfig::DISK_BILLING_IDS] = ['disk-1'];
        $config[WhmcsConfig::IP_GROUP_BILLING_IDS] = ['la-1'];
        $config[WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT] = ['on'];

        return array_replace($config, $overrides);
    }
}
