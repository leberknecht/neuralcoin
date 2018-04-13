<?php

namespace DataModelBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\OrderBookRepository")
 * @ORM\Table(name="order_book",  indexes={
 *          @Index(name="date_symbol_idx", columns={"date", "symbol_id"}),
 *     })
 * @ORM\HasLifecycleCallbacks()
 */
class OrderBook
{
    /**
     * @var string
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var Symbol
     * @ORM\ManyToOne(targetEntity="DataModelBundle\Entity\Symbol", inversedBy="orderBooks")
     */
    private $symbol;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $exchange;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    private $buy = [];

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    private $sell = [];

    /**
     * @var string
     */
    private $rawData;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new DateTime();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Symbol
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @param Symbol $symbol
     */
    public function setSymbol(Symbol $symbol)
    {
        $this->symbol = $symbol;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange(string $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * @param string $rawData
     */
    public function setRawData(string $rawData)
    {
        $this->rawData = $rawData;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return array
     */
    public function getBuy(): array
    {
        return $this->buy;
    }

    /**
     * @param array $buy
     */
    public function setBuy(array $buy)
    {
        $this->buy = $buy;
    }

    /**
     * @return array
     */
    public function getSell(): array
    {
        return $this->sell;
    }

    /**
     * @param array $sell
     */
    public function setSell(array $sell)
    {
        $this->sell = $sell;
    }
}
