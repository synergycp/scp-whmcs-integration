<?php

namespace Scp\Api;

interface ApiTransporter
{
    /**
     * Dispatch the request.
     *
     * @param string $method
     * @param string $url
     * @param array  $postData
     * @param array  $headers
     *
     * @return ApiResponse
     *
     * @throws ApiError
     */
    public function call($method, $url, $postData, array $headers);
}
