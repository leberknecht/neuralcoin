<?php

namespace DataModelBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Class Quote
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\TradeRepository")
 * @ORM\Table(name="trade",
 *     uniqueConstraints={@UniqueConstraint(name="unique_trade",columns={"exchange_name", "symbol_id", "time"})},
 *     indexes={
 *          @Index(name="last_price_idx", columns={"symbol_id", "created_at"}),
 *          @Index(name="last_price_time_idx", columns={"symbol_id", "time"}),
 *          @Index(name="time_idx", columns={"time"}),
 *      }
 *     )
 * @ORM\HasLifecycleCallbacks()
 */
class Trade
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="exchange_name", type="string")
     * @Groups({"network-create-preview", "high-raises"})
     */
    private $exchangeName;

    /**
     * @var Symbol
     * @ORM\ManyToOne(targetEntity="DataModelBundle\Entity\Symbol", inversedBy="trades", cascade={"persist"})
     * @ORM\JoinColumn(name="symbol_id")
     * @Groups({"high-raises"})
     */
    private $symbol;

    /**
     * @var float
     * @ORM\Column(type="decimal", precision=16, scale=8)
     * @Groups({"network-create-preview", "high-raises", "symbol-data"})
     */
    private $price;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var float
     * @ORM\Column(type="float")
     * @Groups({"high-raises"})
     */
    private $volume = 0.0;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     * @Groups({"high-raises", "symbol-data"})
     */
    private $time;

    /**
     * @var float
     */
    private $unnormalizedPrice;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->setCreatedAt(new DateTime());
    }


    /**
     * @return string
     */
    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    /**
     * @param string $exchangeName
     */
    public function setExchangeName(string $exchangeName)
    {
        $this->exchangeName = $exchangeName;
    }

    /**
     * @return Symbol
     */
    public function getSymbol():Symbol
    {
        return $this->symbol;
    }

    /**
     * @param Symbol $symbol
     * @return $this
     */
    public function setSymbol(Symbol $symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice():float
    {
        return (float) $this->price;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt():DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return float
     */
    public function getVolume(): float
    {
        return $this->volume;
    }

    /**
     * @param float $volume
     */
    public function setVolume(float $volume)
    {
        $this->volume = $volume;
    }

    /**
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime $time
     * @return $this
     */
    public function setTime(DateTime $time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @Groups({"network-create-preview"})
     * @return string
     */
    public function getFormattedTime():string
    {
        return $this->getTime()->format(DateTime::ISO8601);
    }

    /**
     * @return float
     */
    public function getUnnormalizedPrice()
    {
        return $this->unnormalizedPrice;
    }

    /**
     * @param float $unnormalizedPrice
     */
    public function setUnnormalizedPrice(float $unnormalizedPrice)
    {
        $this->unnormalizedPrice = $unnormalizedPrice;
    }
}
