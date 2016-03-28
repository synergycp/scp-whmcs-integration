<?php

class ApiServerTest extends ApiTestCase
{
    public function testList()
    {
        $servers = $this->api->get('server')->data();
        $this->assertEquals(get_class($servers), stdClass::class);
    }
}
