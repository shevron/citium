<?php

namespace Citium;

use Zend\Uri\Http as HttpUri;

class Client
{
    use ConnectionTrait;

    public function __construct($baseUrl)
    {
        $this->setBaseUrl($baseUrl);
    }

    /**
     * List all server databases
     *
     * @return array
     * @throws \ErrorException
     */
    public function listDbs()
    {
        $response = $this->sendRequest('GET', '/_all_dbs');

        if ($response->isSuccess()) {
            return $this->parseResponseData($response);
        } else {
            // Invalid response from server
            throw new \ErrorException("Invalid response from server:\n$response");
        }
    }

    /**
     * Create a new database
     *
     * @param  string  $dbName
     * @param  boolean $failIfExists Whether to throw an exception if the DB exists
     * @return \Citium\Database
     */
    public function createDb($dbName, $failIfExists = true)
    {
        // TODO: validate DB name

        $response = $this->sendRequest('PUT', '/' . $dbName);

        if (! $response->isSuccess()) {
            if ($response->getStatusCode() != 412) {
                // Unexpected error code
                throw new Exception\ErrorException("Unable to create new DB: {$response->getReasonPhrase()}", $response->getStatusCode());
            } elseif ($failIfExists) {
                // DB already exists
                throw new Exception\DbConflictException("Database '$dbName' already exists", 412);
            }
        }

        return $this->getDb($dbName);
    }

    /**
     * Get a database object with the base URL and transport object pre-configured
     *
     * @param  string  $dbName
     * @param  boolean $failIfExists Whether to throw an exception if the DB exists
     * @return \Citium\Database
     */
    public function getDb($dbName)
    {
        $db = new Database(HttpUri::merge($this->baseUrl, '/' . $dbName));
        $db->setTransport($this->getTransport());

        return $db;
    }

    /**
     * Delete database
     *
     * Will return true on success or false if the DB does not exist. If some
     * other error happens, will throw an exception.
     *
     * @param  string $dbName
     * @return boolean
     */
    public function deleteDb($dbName)
    {
        $response = $this->sendRequest('DELETE', '/' . $dbName);
        if ($response->isSuccess()) return true;

        if ($response->getStatusCode() == 404) {
            return false;
        } else {
            throw new Exception\ErrorException("Error while attempting to delete DB: {$response->getReasonPhrase()}", $response->getStatusCode());
        }
    }
}
