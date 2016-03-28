<?php

namespace Scp\Api;
use Scp\Support\Collection;
use Scp\Api\ApiModel;
use Scp\Api\Pagination\ApiPaginator;

class ApiQuery
{
    /**
     * @var ApiModel
     */
    protected $model;

    /**
     * @var array
     */
    protected $filters = [];

    public function __construct(ApiModel $model)
    {
        $this->model = $model;
    }

    /**
     * @return ApiModel
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * @return array
     */
    public function filters()
    {
        return $this->filters;
    }

    public function where($key, $value = null)
    {
        $this->filters[$key] = $value;

        return $this;
    }

    /**
     * Run callback on each chunk of items.
     *
     * @param  int     $count    how many in each chunk
     * @param  Closure $callback function given a Collection as only argument
     */
    public function chunk($count, \Closure $callback)
    {
        $page = $this->get($count);
        while ($page) {
            $callback($page);
            $page = $page->nextPage();
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
        return new ApiPaginator($this, $count, $page);
    }

    public function each(\Closure $callback)
    {
        $this->chunk(1000, function (ApiPaginator $page) use ($callback) {
            $page->each(function ($item) use ($callback) {
                $callback($item);
            });
        });
    }
}
