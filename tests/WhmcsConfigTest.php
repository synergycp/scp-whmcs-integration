<?php

use Scp\Whmcs\Whmcs\Whmcs;
use Scp\Whmcs\Whmcs\WhmcsConfig;

class WhmcsConfigTest extends TestCase
{
    private function config(array $params)
    {
        return new WhmcsConfig(new Whmcs($params));
    }

    public function testGetReturnsNullForMissingKey()
    {
        $config = $this->config(['serviceid' => 4]);

        $this->assertSame(4, $config->get('serviceid'));
        $this->assertNull($config->get('missing'));
    }

    public function testGetOptionReturnsNullWhenMissing()
    {
        $config = $this->config(['configoptions' => ['Memory' => 'mem-1']]);

        $this->assertSame('mem-1', $config->getOption('Memory'));
        $this->assertNull($config->getOption('Bandwidth'));
        $this->assertNull($this->config([])->getOption('Memory'));
        $this->assertSame([], $this->config([])->options());
    }

    public function testBillingValuesDoNotDependOnOptionMapping()
    {
        $config = $this->config([
            'configoption' . WhmcsConfig::DELETE_ACTION => 'not a valid choice',
            'configoption' . WhmcsConfig::DISK_BILLING_IDS => 'disk-1, disk-2|1 TB, 2 TB',
        ]);

        $this->assertSame(['disk-1', 'disk-2'], $config->getConfigBillingValues(WhmcsConfig::DISK_BILLING_IDS));
        $this->assertSame(['not a valid choice'], $config->getConfigBillingValues(WhmcsConfig::DELETE_ACTION));
        $this->assertSame(
            ['Drive Bay 1' => '1 TB', 'Drive Bay 2' => '2 TB'],
            $config->getConfigNames(WhmcsConfig::DISK_BILLING_IDS, 'Drive Bay')
        );
        $this->assertNull($config->getConfigBillingValues(WhmcsConfig::MEM_BILLING_ID));
    }

    public function testOptionStillRejectsInvalidDeleteAction()
    {
        $config = $this->config(['configoption' . WhmcsConfig::DELETE_ACTION => 'bogus']);

        $this->expectException(\Exception::class);
        $config->option(WhmcsConfig::DELETE_ACTION);
    }

    public function testConfigForProductToleratesInvalidDeleteAction()
    {
        $config = $this->config(['serviceid' => 1]);
        $product = (object) [
            'id' => 5,
            'configoption' . WhmcsConfig::CPU_BILLING_ID => 'cpu-1',
            'configoption' . WhmcsConfig::DELETE_ACTION => 'bogus',
            'configoption' . WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT => 'on',
        ];

        $result = $config->configForProduct($product);

        $this->assertSame(['cpu-1'], $result[WhmcsConfig::CPU_BILLING_ID]);
        $this->assertSame(['bogus'], $result[WhmcsConfig::DELETE_ACTION]);
        $this->assertSame(['on'], $result[WhmcsConfig::SHOULD_SYNC_INVENTORY_COUNT]);
        $this->assertNull($result[WhmcsConfig::MEM_BILLING_ID]);
        // Params for the current service are restored afterwards.
        $this->assertSame(1, $config->get('serviceid'));
    }
}
