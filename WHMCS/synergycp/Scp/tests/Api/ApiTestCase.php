<?php

use Scp\Api\Api;

abstract class ApiTestCase extends TestCase
{
    protected $url = 'http://dev.synergycp.net/api';
    protected $apiKey = 'bUEaMEOvYK4IBRjunZosES8GIN9M44yXgdKuEI11QZxrrkGJ1S';

    /**
     * @var Api
     */
    protected $api;

    public function setUp()
    {
        $this->api = new Api($this->url, $this->apiKey);
    }
}
