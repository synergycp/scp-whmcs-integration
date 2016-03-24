<?php

namespace Scp\Api;
use Scp\Support\Collection;

abstract class ApiQuery
{
    /**
     * @var Api
     */
    protected $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * Run callback on each chunk of items.
     *
     * @param  int     $count    how many in each chunk
     * @param  Closure $callback function given a Collection as only argument
     */
    public function chunk($count, \Closure $callback)
    {
        
    }

    public function all()
    {
        $result = new Collection;

        $this->each([$result, 'push']);

        return $result;
    }

    public function get($count = 100, $page = 1)
    {

    }

    public function each(\Closure $callback)
    {
        $this->chunk(1000, function (QueryResults $result) {
            $result->each(function ($item) {
                $callback($item);
            });
        });
    }
}
