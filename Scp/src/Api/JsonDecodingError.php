<?php

namespace Scp\Api;

class JsonDecodingError extends ApiError
{
    public function __construct($message, $body)
    {
        parent::__construct($message);
    }
}
