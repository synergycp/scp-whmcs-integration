<?php

use Mockery\MockInterface;
use Scp\Api\Api;
use Scp\Api\ApiResponse;
use Scp\Client\ClientRepository;

class ApiQueryTest extends TestCase
{
    /**
     * @var MockInterface
     */
    private $api;

    /**
     * @var MockInterface
     */
    private $response;

    public function setUp(): void
    {
        $this->api = Mockery::mock(Api::class);
        $this->response = Mockery::mock(ApiResponse::class);
    }

    /**
     * @param array $where
     * @param array $expectedFilters
     *
     * @dataProvider dataFilters
     */
    public function testFilters(array $where, array $expectedFilters)
    {
        $this->api
            ->shouldReceive('get')
            ->with('client/', [
                'page' => 1,
                'per_page' => $perPage = 10,
            ] + $expectedFilters)
            ->andReturn($this->response)
            ;
        $this->response
            ->shouldReceive('data')
            ->andReturn((object) [
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);

        $repository = new ClientRepository($this->api);
        $query = $repository->query();
        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }

        $this->assertEmpty($query->get($perPage)->items());
    }

    public function dataFilters()
    {
        return [
            [
                $where = [
                    'test' => 'Test',
                ], $where,
            ],
        ];
    }

    public function testPagination()
    {
        $curr = 1;
        $items = [
            (object) [
                'id' => 1,
            ],
        ];
        $lastPageNumber = 5;
        $total = 100;
        $perPage = 20;
        $responseData = function () use (&$curr, $items, $lastPageNumber, $total, $perPage) {
            $data = $items;

            return (object) [
                'current_page' => $curr,
                'data' => $data,
                'from' => 1,
                'last_page' => $lastPageNumber,
                'next_page_url' => $curr == $lastPageNumber ? null : '/srv/test?page='.($curr + 1),
                'per_page' => $perPage,
                'prev_page_url' => $curr == 1 ? null : '/srv/test?page='.($curr - 1),
                'to' => $perPage,
                'total' => $total,
            ];
        };
        $this->api
            ->shouldReceive('get')
            ->with('client/', [
                'page' => 1,
                'per_page' => $perPage,
            ])
            ->andReturn($this->response)
            ;
        $this->response
            ->shouldReceive('data')
            ->twice()
            ->andReturnUsing($responseData);

        $repository = new ClientRepository($this->api);
        $pagination = $repository->query()->get($perPage);
        $this->assertEquals($lastPageNumber, $pagination->lastPageNumber());
        $this->assertEquals(1, $pagination->page());
        $this->assertEquals(2, $pagination->nextPageNumber());

        // Get the next page.
        // No request should fire, since items() is not called.
        $nextPage = $pagination->nextPage();
        $this->assertEquals(2, $nextPage->page());

        // Jump to last page.
        // No request will fire yet.
        $nextPage->setPage($lastPageNumber);
        $this->assertEquals($lastPageNumber, $nextPage->page());
        $this->assertEquals($perPage, $nextPage->perPage());

        // Now make sure getting that page works.
        $this->api->shouldReceive('get')
            ->with('client/', [
                'page' => $curr = $lastPageNumber,
                'per_page' => $perPage,
            ])
            ->andReturn($this->response);
        $nextPage->items();
    }
}
