<?php

namespace Scp\Api;

use Scp\Api\Api;

abstract class ApiRepository
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var Api
     */
    protected $api;

    public function __construct(Api $api = null)
    {
        $this->api = $api ?: Api::instance();
    }

    public function make(array $info = [])
    {
        return new $this->class($info, $this->api);
    }

    public function create(array $info = [])
    {
        $item = $this->make($info);

        $item->save();

        return $item;
    }

    public function path()
    {
        return with(new $this->class)->path();
    }

    public function query()
    {
        return new ApiQuery($this->make());
    }
}
