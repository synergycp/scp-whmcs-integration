<?php

namespace Scp\Api;

class ApiTransport implements ApiTransporter
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
    public function call($method, $url, $postData, array $headers)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => 1,
        ));

        $body = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new ApiError(curl_error($curl));
        }

        curl_close($curl);

        return new ApiResponse($body);
    }
}
