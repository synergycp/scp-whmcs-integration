<?php

use Scp\Whmcs\Api;
use Scp\Whmcs\Whmcs\Whmcs;

abstract class ApiTestCase extends TestCase
{
    protected $url = 'http://dev.synergycp.net/api';
    protected $apiKey = 'bUEaMEOvYK4IBRjunZosES8GIN9M44yXgdKuEI11QZxrrkGJ1S';

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Whmcs
     */
    protected $whmcs;

    public function setUp()
    {
        $this->whmcs = Mockery::mock(Whmcs::class);
        $this->whmcs->shouldReceive('getParams')
            ->andReturn([
                'serveraccesshash' => $this->apiKey,
                'serverhostname' => $this->url,
            ]);
        $this->api = new Api($this->whmcs);
    }
}
