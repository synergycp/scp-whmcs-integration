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
        return sprintf(
            'server/%s',
            $this->id
        );
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

    /**
     * Wipe the Server on Synergy.
     *
     * @return $this
     */
    public function wipe()
    {
        return $this->patch(['wiped' => '1']);
    }

    /**
     * Suspend the Server on Synergy.
     *
     * @return $this
     */
    public function suspend()
    {
        return $this->patch(['is_active' => '0']);
    }

    /**
     * Unsuspend the Server on Synergy.
     *
     * @return $this
     */
    public function unsuspend()
    {
        return $this->patch(['is_active' => '1']);
    }

    /**
     * Alias for unsuspend.
     *
     * @return $this
     */
    public function activate()
    {
        return $this->unsuspend();
    }
}
