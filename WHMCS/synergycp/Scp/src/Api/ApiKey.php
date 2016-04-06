<?php

namespace Scp\Api;

use Scp\Client\Client;
use Scp\Server\Server;
use Scp\Support\Arr;

class ApiKey extends ApiModel
{
    /**
     * @var array
     */
    protected $classMap = [
        Client::class => 'client',
    ];

    /**
     * @var ApiModel
     */
    public $owner;

    public function owner(ApiModel $model)
    {
        $this->owner = $model;
        $this->owner_id = $model->getId();
        $this->owner_type = $this->type();

        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        foreach ($this->classMap as $class => $type) {
            if (is_a($this->owner, $class)) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid view model: %s',
            get_class($this->owner)
        ));
    }

    public function path()
    {
        return "key";
    }
}
