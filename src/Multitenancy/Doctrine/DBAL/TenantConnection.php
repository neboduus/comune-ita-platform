<?php

namespace App\Multitenancy\Doctrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Event;
use Doctrine\DBAL\Events;

class TenantConnection extends Connection
{
    /**
     * @var mixed
     */
    protected $params = [];

    /**
     * @var bool
     */
    protected $isConnected = false;

    /**
     * @var bool
     */
    protected $autoCommit = true;

    /**
     * TenantConnection constructor.
     *
     * @param $params
     * @param Driver $driver
     * @param Configuration|null $config
     * @param EventManager|null $eventManager
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __construct($params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        $this->params = $params;
        parent::__construct($params, $driver, $config, $eventManager);
    }

    /**
     * @param string $dbHost
     * @param int $dbPort
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPassword
     */
    public function changeParams(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPassword)
    {
        $this->params['host'] = $dbHost;
        $this->params['port'] = $dbPort;
        $this->params['dbname'] = $dbName;
        $this->params['user'] = $dbUser;
        $this->params['password'] = $dbPassword;
    }

    public function reconnect()
    {
        if ($this->isConnected) {
            $this->close();
        }

        $this->connect();
    }

    public function close()
    {
        $this->_conn = null;

        $this->isConnected = false;
    }

    public function connect()
    {
        if ($this->isConnected) {
            return false;
        }

        $driverOptions = $this->params['driverOptions'] ?? [];
        $user = $this->params['user'] ?? null;
        $password = $this->params['password'] ?? null;

        $this->_conn = $this->_driver->connect($this->params, $user, $password, $driverOptions);
        $this->isConnected = true;

        if ($this->autoCommit === false) {
            $this->beginTransaction();
        }

        if ($this->_eventManager->hasListeners(Events::postConnect)) {
            $eventArgs = new Event\ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        return true;
    }

    /**
     * @return mixed|mixed[]
     */
    public function getParams()
    {
        return $this->params;
    }
}
