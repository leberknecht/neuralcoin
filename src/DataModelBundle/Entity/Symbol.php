<?php

namespace DataModelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\SymbolRepository")
 * @ORM\Table(name="symbol")
 */
class Symbol
{
    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"network-create-preview", "get-exchange-symbols"})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     * @Groups({"network-create-preview", "get-exchange-symbols", "high-raises"})
     */
    private $name;

    /**
     * @var ArrayCollection | Trade[]
     * @ORM\OneToMany(targetEntity="DataModelBundle\Entity\Trade", mappedBy="symbol")
     * @Groups({"network-create-preview"})
     */
    private $trades;

    /**
     * @var ArrayCollection | OrderBook[]
     * @ORM\OneToMany(targetEntity="DataModelBundle\Entity\OrderBook", mappedBy="symbol", fetch="EXTRA_LAZY")
     * @Groups({"network-create-preview"})
     */
    private $orderBooks;

    /**
     * @var integer
     * @ORM\Column(name="trades_count", type="integer", nullable=true)
     */
    private $tradesCount;

    /**
     * @var int
     * @ORM\Column(type="integer", name="average_steps_per_minute", nullable=false, options={"default": 0})
     * @Groups({"get-exchange-symbols"})
     */
    private $averageStepsPerMinute = 0;

    /**
     * @var array
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $exchanges;

    public function __construct()
    {
        $this->exchanges = [];
        $this->trades = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return Trade[]|ArrayCollection
     */
    public function getTrades()
    {
        return $this->trades;
    }

    /**
     * @param Trade[]|ArrayCollection $trades
     */
    public function setTrades(ArrayCollection $trades)
    {
        $this->trades = $trades;
    }

    public function __toString()
    {
        return $this->getName().' ('.$this->getAverageStepsPerMinute() .' trades / min)';
    }

    /**
     * @return int
     */
    public function getTradesCount(): int
    {
        return (int) $this->tradesCount;
    }

    /**
     * @param int $tradesCount
     */
    public function setTradesCount(int $tradesCount)
    {
        $this->tradesCount = $tradesCount;
    }

    /**
     * @return int
     */
    public function getAverageStepsPerMinute(): int
    {
        return $this->averageStepsPerMinute;
    }

    /**
     * @param int $averageStepsPerMinute
     */
    public function setAverageStepsPerMinute(int $averageStepsPerMinute)
    {
        $this->averageStepsPerMinute = $averageStepsPerMinute;
    }

    /**
     * @return array
     */
    public function getExchanges()
    {
        return $this->exchanges;
    }

    /**
     * @param array $exchanges
     */
    public function setExchanges(array $exchanges)
    {
        $this->exchanges = $exchanges;
    }

    /**
     * @return OrderBook[]|ArrayCollection
     */
    public function getOrderBooks()
    {
        return $this->orderBooks;
    }

    /**
     * @param OrderBook[]|ArrayCollection $orderBooks
     */
    public function setOrderBooks($orderBooks)
    {
        $this->orderBooks = $orderBooks;
    }
}
