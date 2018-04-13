<?php

namespace DataModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\PredictionRepository")
 * @ORM\Table(name="prediction", indexes={
 *      @ORM\Index(name="created_at_idx", columns={"created_at"}),
 * }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Prediction
{
    /**
     * @var string
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var Network
     * @ORM\ManyToOne(targetEntity="DataModelBundle\Entity\Network", inversedBy="predictions")
     */
    private $network;
    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @var array
     * @ORM\Column(type="json_array", name="input_data", nullable=true)
     */
    private $inputData = [];

    /**
     * @var array
     * @ORM\Column(type="json_array", name="output_data", nullable=true)
     * @Groups({"dropRaisePrediction"})
     */
    private $outputData;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=true, name="price_at_prediction")
     */
    private $priceAtPrediction;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $finished = false;

    /**
     * @var float
     * @ORM\Column(type="float", name="predicted_value", nullable=true)
     * @Groups({"dropRaisePrediction"})
     */
    private $predictedValue = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="direction_hit", nullable=true, options={"default": null})
     */
    private $directionHit = null;

    /**
     * @var float
     * @ORM\Column(type="float", name="actual_price", nullable=true, options={"default": null})
     */
    private $actualPrice = null;

    /**
     * @var float
     * @ORM\Column(type="float", name="predicted_change", nullable=true, options={"default": null})
     */
    private $predictedChange = null;

    /**
     * @var float
     * @ORM\Column(type="float", name="actual_change", nullable=true, options={"default": null})
     */
    private $actualChange = null;

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new DateTime();
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
     * @return Network
     */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * @param Network $network
     * @return $this
     */
    public function setNetwork(Network $network)
    {
        $this->network = $network;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
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
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return array
     */
    public function getInputData(): array
    {
        return $this->inputData;
    }

    /**
     * @param array $inputData
     */
    public function setInputData(array $inputData)
    {
        $this->inputData = $inputData;
    }

    /**
     * @return array
     */
    public function getOutputData()
    {
        return $this->outputData;
    }

    /**
     * @param array $outputData
     */
    public function setOutputData(array $outputData)
    {
        $this->outputData = $outputData;
    }

    /**
     * @return float
     */
    public function getPriceAtPrediction()
    {
        return $this->priceAtPrediction;
    }

    /**
     * @param float $priceAtPrediction
     */
    public function setPriceAtPrediction(float $priceAtPrediction)
    {
        $this->priceAtPrediction = $priceAtPrediction;
    }

    /**
     * @return boolean
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * @param boolean $finished
     */
    public function setFinished(bool $finished)
    {
        $this->finished = $finished;
    }

    /**
     * @return float
     */
    public function getPredictedValue()
    {
        return $this->predictedValue;
    }

    /**
     * @param float $predictedValue
     */
    public function setPredictedValue($predictedValue)
    {
        $this->predictedValue = $predictedValue;
    }

    /**
     * @return bool
     */
    public function isDirectionHit()
    {
        return $this->directionHit;
    }

    /**
     * @param bool $directionHit
     */
    public function setDirectionHit(bool $directionHit)
    {
        $this->directionHit = $directionHit;
    }

    /**
     * @return float
     */
    public function getActualPrice()
    {
        return $this->actualPrice;
    }

    /**
     * @param float $actualPrice
     */
    public function setActualPrice(float $actualPrice)
    {
        $this->actualPrice = $actualPrice;
    }

    /**
     * @return float
     */
    public function getPredictedChange()
    {
        return $this->predictedChange;
    }

    /**
     * @param float $predictedChange
     */
    public function setPredictedChange($predictedChange)
    {
        $this->predictedChange = $predictedChange;
    }

    /**
     * @return float
     */
    public function getActualChange()
    {
        return $this->actualChange;
    }

    /**
     * @param float $actualChange
     */
    public function setActualChange(float $actualChange)
    {
        $this->actualChange = $actualChange;
    }
}
