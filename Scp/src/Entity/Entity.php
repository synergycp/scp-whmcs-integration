<?php

namespace Scp\Entity;
use Scp\Api\ApiModel;

class Entity extends ApiModel
{
    public function path()
    {
        return "entity/".$this->id;
    }
}
