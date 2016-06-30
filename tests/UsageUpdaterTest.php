<?php

use Scp\Api\ApiResponse;
use Scp\Whmcs\Api;
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
        $this->updater = new UsageUpdater(
            $this->api = Mockery::mock(Api::class),
            $this->database = Mockery::mock(Database::class),
            $this->log = Mockery::mock(LogFactory::class),
            $this->format = Mockery::mock(UsageFormatter::class),
            $this->servers = Mockery::mock(ServerRepository::class)
        );

        $this->server = Mockery::mock(Server::class);
        $this->response = Mockery::mock(ApiResponse::class);
    }

    public function testRun()
    {
        $this->servers->shouldReceive('findByBillingId')
            ->once()
            ->with($billingId = 1)
            ->andReturn($this->server);
        $this->log->shouldReceive('activity')->with(
            'SynergyCP: Updating billing ID %s', $billingId
        );
        $this->server->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($serverId = 1);

        $url = sprintf('server/%d/port', $serverId);
        $this->api->shouldReceive('get')
            ->with($url, ['is_billable' => true])
            ->andReturn($this->response);

        $data = new stdClass;
        $data->data = [
            (object) [
                'id' => 1,
            ],
            (object) [
                'id' => 2,
            ],
        ];
        $this->response->shouldReceive('data')
            ->andReturn($data);
        $data1 = (object) [
            'used' => 600 * 1000,
            'max' => 5000 * 1000,
        ];
        $data2 = (object) [
            'used' => 400 * 1000,
            'max' => 3000 * 1000,
        ];
        $response1 = Mockery::mock(ApiResponse::class);
        $response1->shouldReceive('data')
            ->andReturn($data1);
        $response2 = Mockery::mock(ApiResponse::class);
        $response2->shouldReceive('data')
            ->andReturn($data2);

        $this->api->shouldReceive('get')
            ->with($url.'/1/bandwidth/usage')
            ->andReturn($response1)
            ;
        $this->api->shouldReceive('get')
            ->with($url.'/2/bandwidth/usage')
            ->andReturn($response2)
            ;

        $usedInput = $data1->used + $data2->used;
        $limitInput = $data1->max + $data2->max;
        $this->format->shouldReceive('bitsToMB')
            ->with($usedInput, 3)
            ->andReturn($usedOutput = 1000);
        $this->format->shouldReceive('bitsToMB')
            ->with($limitInput, 3)
            ->andReturn($limitOutput = 800);
        $query = Mockery::mock(Illuminate\Database\Query\Builder::class);
        $this->database->shouldReceive('table')
            ->with('tblhosting')
            ->andReturn($query);
        $query->shouldReceive('where')
            ->with('id', $billingId)
            ->andReturn($query);
        $query->shouldReceive('update')
            ->with([
                'bwusage' => $usedOutput * 8,
                'bwlimit' => $limitOutput * 8,
                'lastupdate' => 'now()',
            ]);

        $this->log->shouldReceive('activity')->with(
            'SynergyCP: Completed usage update'
        );

        $this->updater->run($billingId);
    }
}
