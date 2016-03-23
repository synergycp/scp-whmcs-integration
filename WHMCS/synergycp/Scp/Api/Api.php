<?php

namespace Scp\Api;

class Api
{
    public function call()
    {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => get_url($params),
            CURLOPT_POST => count($post),
            CURLOPT_POSTFIELDS => http_build_query($post),
            CURLOPT_RETURNTRANSFER => 1,
        ));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            print "Synergy CP API Request Error: " . curl_error($ch);
            return "";
        }
        curl_close($ch);

        return new ApiResponse();
    }
}
