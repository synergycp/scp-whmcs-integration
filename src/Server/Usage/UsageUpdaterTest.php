<?php

namespace Scp\Whmcs\Server\Usage;

use PHPUnit\Framework\Attributes\DataProvider;

use Mockery\MockInterface;
use Scp\Api\ApiQuery;
use Scp\Api\ApiResponse;
use Scp\Server\Server;
use Scp\Server\ServerRepository;
use Scp\Support\Collection;
use Scp\Whmcs\Api;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\LogFactory;
use Mockery;
use Scp\Whmcs\Server\Inventory\InventorySynchronizer;
use stdClass;

class UsageUpdaterTest
    extends \TestCase
{
    public function setUp(): void
    {
        $this->updater = new UsageUpdater(
            $this->api = Mockery::mock(Api::class),
            $this->database = Mockery::mock(Database::class),
            $this->log = Mockery::mock(LogFactory::class),
            $this->format = Mockery::mock(UsageFormatter::class),
            $servers = Mockery::mock(ServerRepository::class),
            $inventory = Mockery::mock(InventorySynchronizer::class)
        );

        $inventory->shouldReceive('sync');
        $this->response = Mockery::mock(ApiResponse::class);
        $this->query = Mockery::mock(ApiQuery::class);
        $servers
            ->shouldReceive('query')
            ->once()
            ->andReturn($this->query)
        ;
        $this->query
            ->shouldReceive('where')
            ->withArgs(['integration_id', 'me'])
            ->andReturn($this->query)
        ;
    }

    public function testRunAndLogErrorsReportsAnyFailure()
    {
        $this->query->shouldReceive('chunk')->andThrow(new \RuntimeException('database unavailable'));
        $this->log
            ->shouldReceive('activity')
            ->once()
            ->with('SynergyCP: Error running usage update: %s', 'database unavailable')
        ;

        $this->assertFalse($this->updater->runAndLogErrors());
    }

    #[DataProvider('dataStatus')]
    public function testRun($isActive, $expectedStatus)
    {
        $query = Mockery::mock(Illuminate\Database\Query\Builder::class);
        $this->database
            ->shouldReceive('table')
            ->with('tblhosting')
            ->andReturn($query)
        ;
        $query->shouldReceive('where')->with('id', 2)->andReturnSelf();
        $query->shouldReceive('whereNotIn')->with('domainstatus', ['Terminated'])->andReturnSelf();
        $query->shouldReceive('whereIn')->with('domainstatus', ['Active', 'Suspended'])->andReturnSelf();
        $query
            ->shouldReceive('update')
            ->with(['bwusage' => 1000, 'bwlimit' => 800])
            ->twice()
        ;
        $query
            ->shouldReceive('update')
            ->with(['domainstatus' => $expectedStatus])
            ->twice()
        ;

        $items = array_map(function (MockInterface $server) use ($isActive) {
            $billing = new stdClass();
            $billing->id = 2;
            $usage = new stdClass();
            $usage->used = 600 * 1000;
            $usage->max = 5000 * 1000;
            $access = new stdClass();
            $access->is_active = $isActive;
            $server
                ->shouldReceive('getAttribute')
                ->with('billing')
                ->andReturn($billing)
            ;
            $server->shouldReceive('getAttribute')
                   ->with('usage')
                   ->andReturn($usage)
            ;
            $server->shouldReceive('getAttribute')
                   ->with('access')
                   ->andReturn($access)
            ;

            $this->format
                ->shouldReceive('bitsToMB')
                ->with($usage->used, 3)
                ->andReturn(1000)
            ;
            $this->format
                ->shouldReceive('bitsToMB')
                ->with($usage->max, 3)
                ->andReturn(800)
            ;

            return $server;
        }, [
            Mockery::mock(Server::class),
            Mockery::mock(Server::class),
        ]);
        $this->query
            ->shouldReceive('chunk')
            ->andReturnUsing(function ($_, $callback) use ($items) {
                $callback(new Collection($items));

                // Test has to make assertions
                $this->assertEquals(true, true);
            })
        ;

        $this->log
            ->shouldReceive('activity')
            ->with('SynergyCP: Completed usage update')
        ;

        $this->updater->run();
    }

    public static function dataStatus()
    {
        return [
            'active access' => [true, 'Active'],
            'inactive access' => [false, 'Suspended'],
        ];
    }
}
