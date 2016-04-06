<?php

namespace Scp\Server;
use Scp\Api\ApiModel;
use Scp\Entity\Entity;
use Scp\Support\Collection;

class Server extends ApiModel
{
    /**
     * @var Collection|null
     */
    protected $entities;

    public function path()
    {
        return "server/" . $this->id;
    }

    public function entities()
    {
        if ($this->entities !== null) {
            return $this->entities;
        }

        $this->entities = Entity::query()
            ->where('server_id', $this->getId())
            ->all();

        return $this->entities;
    }
}
