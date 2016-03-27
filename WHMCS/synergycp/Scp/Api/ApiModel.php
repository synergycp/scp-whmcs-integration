<?php

namespace Scp\Api;
use Scp\Api\Api;

abstract class ApiModel
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var bool
     */
    protected $exists = false;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(array $info = [], Api $api = null)
    {
        $this->api = $api ?: Api::instance();
        $this->attributes = $info + $this->attributes;
    }

    abstract public function path();

    public function exists()
    {
        return $this->exists;
    }

    public function setExists($exists)
    {
        $this->exists = $exists;
    }

    public function save()
    {
        print_r($this->attributes);

        if ($this->exists()) {
            return $this->patch();
        }

        return $this->create();
    }

    /**
     * @return $this
     */
    protected function patch()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function create()
    {
        return $this;
    }

    public function __set($attribute, $value)
    {
        return $this->attributes[$attribute] = $value;
    }
}
