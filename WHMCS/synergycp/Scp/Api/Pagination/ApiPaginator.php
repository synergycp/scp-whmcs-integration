<?php

namespace Scp\Api\Pagination;

use Scp\Support\Collection;
use Scp\Api\ApiQuery;
use Scp\Server\Server;

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

    /**
     * @var int
     */
    protected $page;

    /**
     * @var int
     */
    protected $lastPage;

    /**
     * @var int
     */
    protected $total;

    public function __construct(ApiQuery $query, $perPage = 20, $page = 1)
    {
        $this->page = $page;
        $this->query = $query;
        $this->perPage = $perPage;
    }

    /**
     * @return Collection
     */
    public function items()
    {
        if (!$this->items) {
            $this->refresh();
        }

        return $this->items;
    }

    public function refresh()
    {
        $model = $this->query->model();
        $api = $model->api();
        $response = $api->get(
            $model->path(),
            $this->query->filters()
        );

        $data = $response->data();

        $this->items = new Collection($data->data);
        $this->items->transform(function($item) use ($api) {
            return new Server((array) $item, $api);
        });
        $this->page = $data->current_page;
        $this->lastPage = $data->last_page;
        $this->total = $data->total;

        return $this;
    }

    public function page()
    {
        return $this->page;
    }

    public function setPage($page)
    {
        $this->clear();
        $this->page = $page;

        return $this;
    }

    public function clear()
    {
        $this->items = null;

        return $this;
    }

    /**
     * @param  \Closure|callable $callback
     *
     * @return $this
     */
    public function each($callback)
    {
        $this->items()->each($callback);

        return $this;
    }

    /**
     * Modify items in place.
     *
     * @param  \Closure|callable $callback
     *
     * @return $this
     */
    public function transform($callback)
    {
        $this->items()->transform($callback);

        return $this;
    }

    /**
     * @param  \Closure|callable $callback
     *
     * @return static
     */
    public function map($callback)
    {
        return $this->copy()->transform($callback);
    }

    /**
     * @return int
     */
    public function lastPage()
    {
        return $this->lastPage;
    }

    /**
     * @return int|null
     */
    public function nextPageNumber()
    {
        $page = $this->page();
        if ($page == $this->lastPage()) {
            return;
        }

        return $page + 1;
    }

    /**
     * @return static|null
     */
    public function nextPage()
    {
        $nextPageNumber = $this->nextPageNumber();
        if (!$nextPageNumber) {
            return;
        }

        return $this->copy()->setPage($nextPageNumber);
    }

    public function copy()
    {
        return clone $this;
    }
}
