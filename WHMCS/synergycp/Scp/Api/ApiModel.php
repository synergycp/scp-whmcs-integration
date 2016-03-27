<?php

namespace Scp\Api;
use Scp\Api\Api;
use Scp\Support\Arr;

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

    public function getId()
    {
        return $this->id;
    }

    public function setExists($exists)
    {
        $this->exists = $exists;
    }

    public function save()
    {
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
        $this->api->patch($this->path(), $this->attributes);

        return $this;
    }

    /**
     * @return $this
     */
    protected function create()
    {
        $this->api->post($this->path(), $this->attributes);

        return $this;
    }

    public function __set($attribute, $value)
    {
        return $this->attributes[$attribute] = $value;
    }

    public function __get($attribute)
    {
        return $this->getAttribute($attribute);
    }

    public function getAttribute($attribute)
    {
        return Arr::get($this->attributes, $attribute);
    }
}
