<?php
use Scp\Whmcs\Server\Usage\UsageUpdater;
use Scp\Whmcs\Server\Usage\UsageFormatter;
use Scp\Whmcs\Database\Database;
use Scp\Whmcs\LogFactory;
use Scp\Server\ServerRepository;
use Scp\Api\Api;
use Scp\Api\ApiQuery;

class UsageUpdaterTest extends TestCase
{
    public function testRun()
    {
        $api = Mockery::mock(Api::class);
        $log = Mockery::mock(LogFactory::class);
        $format = Mockery::mock(UsageFormatter::class);
        $servers = Mockery::mock(ServerRepository::class, [$api]);
        $database = Mockery::mock(Database::class);

        $servers->shouldReceive('make')->passthru();
        $servers->shouldReceive('query')->passthru();
        $log->shouldReceive('activity')->with(
            "SynergyCP: Completed usage update"
        );

        $updater = new UsageUpdater($database, $log, $format, $servers);
        $updater->run();
    }
}
