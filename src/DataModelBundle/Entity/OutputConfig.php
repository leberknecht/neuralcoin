<?php

namespace DataModelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity()
 * @ORM\Table(name="output_config")
 */
class OutputConfig
{
    const OUTPUT_TYPE_PREDICT_ONE_PRICE = 1;
    const OUTPUT_TYPE_HAS_RAISED_BY = 2;
    const OUTPUT_TYPE_HAS_DROPPED_BY = 3;

    public static $formChoiceMapping = [
        'Predict future value of an Input (regression)' => self::OUTPUT_TYPE_PREDICT_ONE_PRICE,
        'Has raised by X % (classification)' => self::OUTPUT_TYPE_HAS_RAISED_BY,
        'Has dropped by X % (classification)' => self::OUTPUT_TYPE_HAS_DROPPED_BY,
    ];

    /**
     * @var integer
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    private $type = self::OUTPUT_TYPE_PREDICT_ONE_PRICE;

    /**
     * @var Symbol
     * @ORM\ManyToOne(targetEntity="Symbol")
     * @ORM\JoinColumn(name="price_prediction_symbol_id")
     */
    private $pricePredictionSymbol;

    /**
     * @var Network
     * @ORM\OneToOne(targetEntity="Network", inversedBy="outputConfig")
     */
    private $network;

    /**
     * @var int
     * @ORM\Column(name="steps_ahead", type="integer")
     */
    private $stepsAhead = 10;

    /**
     * @var float
     * @ORM\Column(name="threshold_percentage", type="float", nullable=true)
     */
    private $thresholdPercentage = null;

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
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type)
    {
        $this->type = $type;
    }

    public function getTypeString()
    {
        $index = array_search($this->getType(), array_values(self::$formChoiceMapping));
        return array_keys(self::$formChoiceMapping)[$index];
    }
    /**
     * @return Symbol
     */
    public function getPricePredictionSymbol()
    {
        return $this->pricePredictionSymbol;
    }

    /**
     * @param Symbol $pricePredictionSymbol
     * @return $this
     */
    public function setPricePredictionSymbol(Symbol $pricePredictionSymbol)
    {
        $this->pricePredictionSymbol = $pricePredictionSymbol;

        return $this;
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
     * @return int
     */
    public function getStepsAhead(): int
    {
        return $this->stepsAhead;
    }

    /**
     * @param int $stepsAhead
     */
    public function setStepsAhead(int $stepsAhead)
    {
        $this->stepsAhead = $stepsAhead;
    }

    /**
     * @return float
     */
    public function getThresholdPercentage()
    {
        return $this->thresholdPercentage;
    }

    /**
     * @param float $thresholdPercentage
     *
     * @return $this
     */
    public function setThresholdPercentage(float $thresholdPercentage)
    {
        $this->thresholdPercentage = $thresholdPercentage;
        return $this;
    }
}
