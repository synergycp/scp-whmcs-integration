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
        $item = new $this->class;

        foreach ($info as $key => $value) {
            $item->{$key} = $value;
        }

        return $item;
    }

    public function create(array $info = [])
    {
        $item = $this->make($info);

        $item->save();

        return $item;
    }
}
