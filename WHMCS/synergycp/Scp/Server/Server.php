<?php

namespace Scp\Server;
use Scp\Api\ApiModel;

class Server extends ApiModel
{
    public function path()
    {
        return "server/" . $this->id;
    }
}
