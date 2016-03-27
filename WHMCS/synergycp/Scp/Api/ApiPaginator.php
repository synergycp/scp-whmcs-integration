<?php

namespace Scp\Api;

use Scp\Support\Collection;

class ApiPaginator
{
    /**
     * @var Collection
     */
    protected $items;

    /**
     * @var ApiQuery
     */
    protected $query;

    public function __construct(ApiQuery $query)
    {
        $this->query = $query;
        //$this->current = $apiResponse['curr_page'];
        //$this->total = $apiResponse['total'];
    }

    /**
     * @return Collection
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * @param  \Closure|callable $callback
     *
     * @return $this
     */
    public function each($callback)
    {
        $this->items->each($callback);

        return $this;
    }

    /**
     * @param  \Closure|callable $callback
     *
     * @return static
     */
    public function map($callback)
    {
        $newThis = clone $this;
        $newThis->items = $this->items->map($callback);

        return $newThis;
    }

    /**
     * @return int|null
     */
    public function nextPage()
    {
        //
    }
}
