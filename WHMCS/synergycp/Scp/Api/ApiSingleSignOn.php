<?php

namespace Scp\Api;

use Scp\Api\Api;
use Scp\Api\ApiModel;
use Scp\Server\Server;
use Scp\Support\Arr;

class ApiSingleSignOn
{
    /**
     * @var array
     */
    protected $classMap = [
        Server::class => 'server',
    ];

    /**
     * @var ApiModel|null
     */
    protected $view;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var ApiKey
     */
    protected $key;

    public function __construct(
        ApiKey $key,
        Api $api = null
    ) {
        $this->key = $key;
        $this->api = $api ?: Api::instance();
    }

    public function view(ApiModel $model)
    {
        $this->view = $model;
        $this->viewType();

        return $this;
    }

    public function viewType()
    {
        if (!$this->view) {
            return;
        }

        foreach ($this->classMap as $class => $type) {
            if (is_a($this->view, $class)) {
                return $type;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid view model: %s',
            get_class($this->view)
        ));
    }

    public function viewId()
    {
        return $this->view ? $this->view->getId() : null;
    }

    public function url()
    {
        $data = [
            'key' => $this->key->key,
        ];

        if ($type = $this->viewType()) {
            $data['view_type'] = $type;
        }

        if ($viewId = $this->viewId()) {
            $data['view_id'] = $viewId;
        }

        return $this->api->url('sso', $data);
    }
}
