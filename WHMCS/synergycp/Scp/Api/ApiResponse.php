<?php

namespace Scp\Api;

class ApiResponse
{
    /**
     * @var string
     */
    protected $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    private function jsonError()
    {
        static $errors = array(
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        );
        $error = json_last_error();
        if (array_key_exists($error, $errors)) {
            throw new JsonDecodingError($errors[$error]);
        }
    }

    public function data()
    {
        $resp = json_decode($this->body);
        if (!$resp) {
            $this->jsonError();
        }

        if (!empty($resp->msgs))
            foreach ($resp->msgs as $msg)
                if ($msg->cat == 'danger')
                    return $msg->text;

        if (empty($resp->result))
            $resp->result = "success";

        return $resp->data;
    }
}
