<?php

class ApiQueryTest extends TestCase
{
    public function test()
    {
        // $this->servers->shouldReceive('make')->passthru();
        $curr = 0;
        $items = [
            (object) [
                'id' => 1,
            ],
        ];
        $lastPage = 5;
        $total = 100;
        $responseData = function () use (&$curr, $items, $lastPage, $total) {
            $curr++;
            $data = $items;
            return (object) [
                'current_page' => $curr,
                'data' => $data,
                'from' => 1,
                'last_page' => $lastPage,
                'next_page_url' => $curr == $lastPage ? null : '/srv/test?page='.($curr+1),
                'per_page' => $perPage = count($data),
                'prev_page_url' => $curr == 1 ? null : '/srv/test?page='.($curr-1),
                'to' => $perPage,
                'total' => $total,
            ];
        };
        /*$this->response->shouldReceive('data')
            ->times($lastPage)
            ->andReturnUsing($responseData);*/
    }
}
