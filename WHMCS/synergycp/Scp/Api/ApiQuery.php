<?php

namespace Scp\Api;
use Scp\Support\Collection;
use Scp\Api\ApiModel;

class ApiQuery
{
    /**
     * @var ApiModel
     */
    protected $model;

    public function __construct(ApiModel $model)
    {
        $this->model = $model;
    }

    /**
     * Run callback on each chunk of items.
     *
     * @param  int     $count    how many in each chunk
     * @param  Closure $callback function given a Collection as only argument
     */
    public function chunk($count, \Closure $callback)
    {
        $page = 1;
        while ($page) {
            $items = $this->get($count, $page);
            $items->each($callback);
            $page = $items->nextPage();
        }
    }

    public function all()
    {
        $result = new Collection;

        $this->each([$result, 'push']);

        return $result;
    }

    public function get($count = 100, $page = 1)
    {
        return new ApiPaginator;
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
