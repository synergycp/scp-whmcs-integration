<?php

use Scp\Whmcs\Server\Usage\UsageUpdater;
use Scp\Whmcs\Server\Usage\UsageFormatter;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\LogFactory;
use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Api\ApiQuery;

class UsageUpdaterTest extends TestCase
{
    public function setUp()
    {
        $this->log = Mockery::mock(LogFactory::class);
        $this->query = Mockery::mock(ApiQuery::class);
        $this->server = Mockery::mock(Server::class);
        $this->format = Mockery::mock(UsageFormatter::class);
        $this->servers = Mockery::mock(ServerRepository::class);
        $this->database = Mockery::mock(Database::class);
    }

    public function testNoBillingId()
    {
        $this->server->shouldReceive('getAttribute')
            ->with('billing_id')
            ->andReturn(null);
        $this->query->shouldReceive('each')
            ->andReturnUsing(function ($callback) {
                $callback($this->server);
            });
        $this->servers->shouldReceive('query')->andReturn($this->query);
        $this->log->shouldReceive('activity')->with(
            'SynergyCP: Completed usage update'
        );

        $updater = new UsageUpdater($this->database, $this->log, $this->format, $this->servers);
        $updater->run();
    }

    public function testRun()
    {
        $this->server->shouldReceive('getAttribute')
            ->with('billing_id')
            ->andReturn($billingId = 1);
        $this->query->shouldReceive('each')
            ->andReturnUsing(function ($callback) {
                $callback($this->server);
            });
        $this->servers->shouldReceive('query')->andReturn($this->query);
        $this->log->shouldReceive('activity')->with(
            'SynergyCP: Updating billing ID %s', $billingId
        );
        $this->server->shouldReceive('getAttribute')
            ->with('bandwidth_used')
            ->andReturn($usedInput = 1000 * 1000);
        $this->server->shouldReceive('getAttribute')
            ->with('bandwidth_limit')
            ->andReturn($limitInput = 8000 * 1000);
        $this->format->shouldReceive('bitsToMB')
            ->with($usedInput)
            ->andReturn($usedOutput = 1000);
        $this->format->shouldReceive('bitsToMB')
            ->with($limitInput, 3)
            ->andReturn($limitOutput = 800);
        $this->database->shouldReceive('update')
            ->with('tblhosting', [
                'bwusage' => $usedOutput,
                'bwlimit' => $limitOutput,
                'lastupdate' => 'now()',
            ], [
                'id' => $billingId,
            ]);
        $this->log->shouldReceive('activity')->with(
            'SynergyCP: Completed usage update'
        );

        $updater = new UsageUpdater($this->database, $this->log, $this->format, $this->servers);
        $updater->run();
    }
}
