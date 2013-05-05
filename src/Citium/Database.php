<?php

namespace Citium;

class Database
{
    use ConnectionTrait;

    public function __construct($baseUrl)
    {
        $this->setBaseUrl($baseUrl);
    }

    public function createDocument($data, $docId = null, $documentClass = null)
    {
        if ($docId) {
            $response = $this->sendDbRequest('PUT', $docId, $data);
        } else {
            $response = $this->sendRequest('POST', '', $data);
        }

        if ($response->isSuccess()) {
            if ($documentClass) {
                // Check document class inherits from Document
                if (! is_subclass_of($documentClass, __NAMESPACE__ . '\\Document', true)) {
                    throw new Exception\InvalidArgumentException("Provided document class is not a Document subclass");
                }

            } else {
                $documentClass = __NAMESPACE__ . '\\Document';
            }

            $document = new $documentClass();
            $document->fromJson($response->getContent())
                     ->setTransport($this->getTransport())
                     ->setBaseUrl($this->getBaseUrl());

            return $document;

        } else {
            throw new Exception\ErrorException("Unable to create new document: {$response->getReasonPhrase()}", $response->getStatusCode());
        }
    }

    public function createMultipleDocuments(array $docs)
    {
        // We have to manually encode before sending to avoid array-as-object encoding
        $response = $this->sendDbRequest('POST', '_bulk_docs', array(
            'docs' => $docs
        ));

        if ($response->isSuccess()) {
            return $this->parseResponseData($response);
        } else {
            throw new Exception\ErrorException("Unable to bulk-insert documents: {$response->getReasonPhrase()}", $response->getStatusCode());
        }
    }

    protected function sendDbRequest($method, $uri, $data = null, $extraHeaders = null)
    {
        $uri = $this->baseUrl->getPath() . '/' . $uri;
        return $this->sendRequest($method, $uri, $data, $extraHeaders);
    }
}