<?php


namespace DataModelBundle\Entity;


use Ratchet\ConnectionInterface;

class WebsocketClient
{
    /**
     * @var string
     */
    private $ip;

    /**
     * @var int
     */
    private $resourceId;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp(string $ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return int
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param int $resourceId
     */
    public function setResourceId(int $resourceId)
    {
        $this->resourceId = $resourceId;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param ConnectionInterface $connection
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}