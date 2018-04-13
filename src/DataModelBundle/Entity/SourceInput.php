<?php

namespace DataModelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

class SourceInput
{
    /**
     * @var string
     * @Groups({"network-create-preview"})
     */
    private $symbolName;

    /**
     * @var int
     * @Groups({"network-create-preview"})
     */
    private $symbolId;

    /**
     * @var TradeData[] | ArrayCollection
     * @Groups({"network-create-preview"})
     */
    private $tradesData;

    /**
     * @var float
     */
    private $maxPrice = 0.0;

    /**
     * @var float
     */
    private $minPrice = 0.0;

    public function __construct()
    {
        $this->tradesData = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getSymbolName(): string
    {
        return $this->symbolName;
    }

    /**
     * @param string $symbolName
     */
    public function setSymbolName(string $symbolName)
    {
        $this->symbolName = $symbolName;
    }

    /**
     * @return ArrayCollection|TradeData[]
     */
    public function getTradesData()
    {
        return $this->tradesData;
    }

    /**
     * @param ArrayCollection|TradeData[] $tradesData
     */
    public function setTradesData(ArrayCollection $tradesData)
    {
        $this->tradesData = $tradesData;
    }

    public function addTradeData(TradeData $tradeData)
    {
        $this->tradesData->add($tradeData);
    }

    /**
     * @return int
     */
    public function getSymbolId()
    {
        return $this->symbolId;
    }

    /**
     * @param int $symbolId
     */
    public function setSymbolId($symbolId)
    {
        $this->symbolId = $symbolId;
    }

    /**
     * @return float
     */
    public function getMaxPrice(): float
    {
        return $this->maxPrice;
    }

    /**
     * @param float $maxPrice
     * @return $this
     */
    public function setMaxPrice(float $maxPrice)
    {
        $this->maxPrice = $maxPrice;

        return $this;
    }

    /**
     * @return float
     */
    public function getMinPrice(): float
    {
        return $this->minPrice;
    }

    /**
     * @param float $minPrice
     * @return $this
     */
    public function setMinPrice(float $minPrice)
    {
        $this->minPrice = $minPrice;

        return $this;
    }
}
