<?php

namespace Citium;

use ZHttpClient2\Response;

class Document // TODO: implement ArrayAccess ?
{
    use ConnectionTrait;

    /**
     * Data object
     *
     * @var stdClass
     */
    protected $data = null;

    /**
     * Populate data from JSON string
     *
     * @return Citium\Document
     */
    public function fromJson($jsonString)
    {
        $data = json_decode($jsonString);

        if (! is_object($data)) {
            throw new Exception\ErrorException("Unexpected content in response body");
        }

        $this->data = $data;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray()
    {
        return (array) $this->data;
    }

    public function __get($var)
    {
        if (isset($this->data->{$var})) {
            return $this->data->{$var};
        } else {
            return null;
        }
    }

    public function __set($var, $value)
    {
        $this->data->$var = $value;
    }

    public function __isset($var)
    {
        return isset($this->data->$var);
    }

    public function __unset($var)
    {
        unset($this->data->$var);
    }
}