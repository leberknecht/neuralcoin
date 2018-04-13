<?php


namespace DataModelBundle\Entity;


use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

class TradeData
{
    /**
     * @var DateTime
     * @Groups({"network-create-preview"})
     */
    private $time;

    /**
     * @var float
     * @Groups({"network-create-preview"})
     */
    private $price;

    /**
     * @return DateTime
     */
    public function getTime(): DateTime
    {
        return $this->time;
    }

    /**
     * @param DateTime $time
     */
    public function setTime(DateTime $time)
    {
        $this->time = $time;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
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
}
