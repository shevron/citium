<?php

namespace Citium;

use ZHttpClient2\Request as HttpRequest;
use ZHttpClient2\Response as HttpResponse;
use ZHttpClient2\Transport\Transport as HttpTransportInterface;
use ZHttpClient2\Transport\Socket as HttpSocketTransport;
use Zend\Uri\Http as HttpUri;

trait ConnectionTrait
{
    /**
     * Get the base URL
     *
     * @var Zend\Uri\Http
     */
    protected $baseUrl = null;

    /**
     * Get the transport object
     *
     * @var ZHttpClient2\Transport\Transport
     */
    protected $transport = null;

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Set the base URL for the connecting object
     *
     * @param  Zend\Uri\Http | string $url
     * @return Citium\ConnectionTrait
     */
    public function setBaseUrl($url)
    {
        if (! $url instanceof HttpUri) {
            $url = new HttpUri($url);
        }

        $url->normalize();

        if (! ($url->isAbsolute() && $url->isValid())) {
            throw new Exception\InvalidArgumentException("Invalid base URL provided: $url");
        }

        $this->baseUrl = $url;

        return $this;
    }

    /**
     * Get the transport object. If no transport object was set, create one.
     *
     * @return ZHttpClient2\Transport\Transport
     */
    public function getTransport()
    {
        if (! $this->transport) {
            $this->transport = new HttpSocketTransport();
        }

        return $this->transport;
    }

    /**
     * Set the transport object
     *
     * @param  $transport ZHttpClient2\Transport\Transport
     * @return Citium\ConnectionTrait
     */
    public function setTransport(HttpTransportInterface $transport)
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * Send HTTP request and get response
     *
     * @param  string $method
     * @param  string $uri
     * @param  string $data
     * @param  array  $extraHeaders
     * @return ZHttpClient2\Response
     */
    protected function sendRequest($method, $uri, $data = null, $extraHeaders = null)
    {
        $req = new HttpRequest();
        $req->setUri(HttpUri::merge($this->baseUrl, $uri))
            ->setMethod($method);

        $req->getHeaders()->addHeaderLine('Date', date(DATE_RFC1123));

        if ($method == 'POST' || $method == 'PUT') {
            $this->setRequestData($req, $data);
        }

        return $this->getTransport()->send($req);
    }

    protected function setRequestData(HttpRequest $request, $data)
    {
        $headers = $request->getHeaders();
        if (empty($data)) {
            $headers->addHeaderLine('Content-length', 0);

        } else {
            if (is_array($data) || is_object($data)) {
                $data = json_encode($data);
            }

            if (is_string($data)) {
                // Assume JSON
                $headers->addHeaderLine('Content-length', strlen($data))
                        ->addHeaderLine('Content-type', 'application/json');

                $request->setContent($data);

            } else {
                throw new Exception\InvalidArgumentException("Expecting an array, object, or JSON string");
            }
        }
    }

    /**
     * Parse and return the response data
     *
     * @param  ZHttpClient2\Response $response
     * @return array
     */
    protected function parseResponseData(HttpResponse $response)
    {
        return json_decode($response->getBody());
    }
}