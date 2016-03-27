<?php

namespace Scp\Client;
use Scp\Api\ApiModel;

class Client extends ApiModel
{
    public function path()
    {
        return "client/" . $this->id;
    }
}
