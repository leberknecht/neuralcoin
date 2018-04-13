<?php

namespace DataModelBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\NetworkRepository")
 * @ORM\Table(name="network")
 * @ORM\HasLifecycleCallbacks()
 */
class Network
{
    const DEFAULT_TIME_SCOPE = '4 hours';
    const ACTIVATION_FUNCTION_NONE = 'none';
    const ACTIVATION_FUNCTION_LINEAR = 'linear';
    const ACTIVATION_FUNCTION_TANH = 'tanh';
    const ACTIVATION_FUNCTION_SIGMOID = 'sigmoid';

    const OPTIMIZER_ADAM = 'adam';
    const OPTIMIZER_SGD = 'SGD';
    const OPTIMIZER_RMS_PROP = 'RMSprop';
    const OPTIMIZER_MOMENTUM = 'Momentum';

    const VALUE_TYPE_ABSOLUTE = 'absolute';
    const VALUE_TYPE_PERCENTAGE = 'percentage';
    const VALUE_TYPE_PERCENTAGE_ACCUMULATED = 'accumulated-percentage';

    /**
     * @var string
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var ArrayCollection | Symbol[]
     * @ORM\ManyToMany(targetEntity="DataModelBundle\Entity\Symbol")
     * @ORM\JoinTable(name="symbols_network")
     * @ORM\OrderBy({"name": "ASC"})
     */
    private $symbols;

    /**
     * @var string
     * @Assert\NotBlank()
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", name="time_scope")
     */
    private $timeScope = self::DEFAULT_TIME_SCOPE;

    /**
     * @var bool
     * @ORM\Column(type="string", name="value_type", options={"default": "absolute"})
     */
    private $valueType = self::VALUE_TYPE_ABSOLUTE;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="interpolate_inputs")
     */
    private $interpolateInputs = false;

    /**
     * @var integer
     * @ORM\Column(type="integer", name="interpolation_interval", nullable=true)
     */
    private $interpolationInterval = 5;

    /**
     * @var int
     * @ORM\Column(name="input_steps", type="integer", nullable=false, options={"default": 1})
     */
    private $inputSteps = 1;

    /**
     * @var OutputConfig
     * @ORM\OneToOne(targetEntity="DataModelBundle\Entity\OutputConfig", mappedBy="network", cascade={"persist", "remove"})
     */
    private $outputConfig;

    /**
     * @var string
     * @ORM\Column(type="string", name="activation_function")
     */
    private $activationFunction = self::ACTIVATION_FUNCTION_TANH;

    /**
     * @var int
     * @ORM\Column(type="integer", name="hidden_layers")
     * @Assert\Range(
     *      min = 1,
     *      max = 8,
     *      minMessage = "at least 1 hidden layer is required",
     *      maxMessage = "maxiumum for hidden layers is 8"
     * )
     */
    private $hiddenLayers = 2;

    /**
     * @var string
     * @ORM\Column(type="string", name="file_path", nullable=true)
     */
    private $filePath;

    /**
     * @var float
     * @ORM\Column(type="float", name="mean_error", nullable=true)
     */
    private $meanError = 0.0;

    /**
     * @var TrainingRun[] | ArrayCollection
     * @ORM\OrderBy({"createdAt": "DESC"})
     * @ORM\OneToMany(targetEntity="DataModelBundle\Entity\TrainingRun", mappedBy="network", orphanRemoval=true)
     */
    private $trainingRuns;

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
     * @var int
     * @ORM\Column(type="integer", name="input_length")
     */
    private $inputLength;

    /**
     * @var int
     * @ORM\Column(type="integer", name="output_length")
     */
    private $outputLength;

    /**
     * @var Prediction[] | ArrayCollection
     * @ORM\OrderBy({"createdAt" = "ASC"})
     * @ORM\OneToMany(targetEntity="DataModelBundle\Entity\Prediction", mappedBy="network", orphanRemoval=true)
     */
    private $predictions;

    /**
     * @var float
     * @ORM\Column(type="float")
     */
    private $learningRate = 0.001;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": "0"})
     */
    private $autopilot = false;

    /**
     * @var float
     * @ORM\Column(type="float", name="direction_hit_ratio", nullable=false, options={"default": 0.0})
     */
    private $directionHitRatio = 0.0;

    /**
     * @var int
     * @ORM\Column(type="integer", name="epochs_per_training_run", nullable=false, options={"default": 500})
     */
    private $epochsPerTrainingRun = 500;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="use_dropout", options={"default": 0})
     */
    private $useDropout = false;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=true)
     */
    private $dropout = 0.0;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $bias = false;

    /**
     * @var string
     * @ORM\Column(type="string", options={"default": "adam"})
     */
    private $optimizer = 'adam';

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, name="image_path")
     */
    private $imagePath;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $exchange;

    /**
     * @var bool
     * @ORM\Column(name="generate_image", options={"default": 0})
     */
    private $generateImage = false;

    /**
     * @var bool
     * @ORM\Column(name="shuffle_training_data", options={"default": 0})
     */
    private $shuffleTrainingData = false;

    /**
     * @var integer
     * @ORM\Column(name="balance_hits_and_fails", type="boolean", options={"default": 1})
     */
    private $balanceHitsAndFails = true;

    /**
     * @var bool
     * @ORM\Column(name="separate_input_symbols", type="boolean", options={"default": 0})
     */
    private $separateInputSymbols = false;

    /**
     * @var bool
     * @ORM\Column(name="custom_shape", type="boolean", options={"default": 0})
     */
    private $customShape = false;

    /**
     * @var bool
     * @ORM\Column(name="use_most_active_symbols", type="boolean", options={"default": 0})
     */
    private $useMostActiveSymbols = false;

    /**
     * @var integer
     * @ORM\Column(name="most_active_symbols_count", type="integer", nullable=true)
     */
    private $mostActiveSymbolsCount = null;

    /**
     * @var string
     * @ORM\Column(name="shape", type="string")
     */
    private $shape = '[]';

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    private $useOrderBooks = false;

    /**
     * @var int
     * @ORM\Column(type="integer", name="order_book_steps", nullable=true)
     */
    private $orderBookSteps = 5;

    public function __construct()
    {
        $this->symbols = new ArrayCollection();
        $this->trainingRuns = new ArrayCollection();
        $this->predictions = new ArrayCollection();
    }

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
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function preUpdatePrePersist()
    {
        switch ($this->getOutputConfig()->getType()) {
            case OutputConfig::OUTPUT_TYPE_PREDICT_ONE_PRICE:
                $this->setOutputLength(1);
                break;
            case OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY:
            case OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY:
                $this->setOutputLength(2);
                break;
            default:
                throw new \Exception('unsupported output type given: '.$this->getOutputConfig()->getType());
        }

        if ($this->isSeparateInputSymbols()) {
            $this->setInputLength($this->getInputSteps());
        } else {
            $this->setInputLength($this->getSymbols()->count() * $this->getInputSteps());
        }

        if ($this->useOrderBooks()) {
            $this->setInputLength(
                $this->getInputLength() +
                $this->getInputSteps() * ($this->getSymbols()->count() * $this->orderBookSteps * 4) //sell + buy and price,volume each
            );
        }
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
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Symbol[]|ArrayCollection
     */
    public function getSymbols()
    {
        return $this->symbols;
    }

    /**
     * @param Symbol[]|ArrayCollection $symbols
     */
    public function setSymbols($symbols)
    {
        $this->symbols = $symbols;
    }

    /**
     * @return string
     */
    public function getName()
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
     * @return string
     */
    public function getTimeScope(): string
    {
        return $this->timeScope;
    }

    /**
     * @param string $timeScope
     */
    public function setTimeScope(string $timeScope)
    {
        $this->timeScope = $timeScope;
    }

    /**
     * @return bool
     */
    public function isInterpolateInputs(): bool
    {
        return $this->interpolateInputs;
    }

    /**
     * @param bool $interpolateInputs
     */
    public function setInterpolateInputs(bool $interpolateInputs)
    {
        $this->interpolateInputs = $interpolateInputs;
    }

    /**
     * @return integer
     */
    public function getInterpolationInterval()
    {
        return $this->interpolationInterval;
    }

    /**
     * @param integer $interpolationRatio
     */
    public function setInterpolationInterval($interpolationRatio)
    {
        $this->interpolationInterval = $interpolationRatio;
    }

    /**
     * @return OutputConfig
     */
    public function getOutputConfig()
    {
        return $this->outputConfig;
    }

    /**
     * @param OutputConfig $outputConfig
     * @return $this
     */
    public function setOutputConfig(OutputConfig $outputConfig)
    {
        $this->outputConfig = $outputConfig;
        return $this;
    }

    /**
     * @return int
     */
    public function getInputSteps(): int
    {
        return $this->inputSteps;
    }

    /**
     * @param int $inputSteps
     */
    public function setInputSteps(int $inputSteps)
    {
        $this->inputSteps = $inputSteps;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return float
     */
    public function getMeanError()
    {
        return $this->meanError;
    }

    /**
     * @param float $meanError
     */
    public function setMeanError(float $meanError)
    {
        $this->meanError = $meanError;
    }

    /**
     * @return string
     */
    public function getActivationFunction(): string
    {
        return (string) $this->activationFunction;
    }

    /**
     * @param string $activationFunction
     */
    public function setActivationFunction(string $activationFunction)
    {
        $this->activationFunction = $activationFunction;
    }

    /**
     * @return int
     */
    public function getHiddenLayers(): int
    {
        return $this->hiddenLayers;
    }

    /**
     * @param int $hiddenLayers
     */
    public function setHiddenLayers(int $hiddenLayers)
    {
        $this->hiddenLayers = $hiddenLayers;
    }

    /**
     * @return TrainingRun[] | ArrayCollection
     */
    public function getTrainingRuns()
    {
        return $this->trainingRuns;
    }

    /**
     * @param TrainingRun[]|ArrayCollection $trainingRuns
     */
    public function setTrainingRuns(ArrayCollection $trainingRuns)
    {
        $this->trainingRuns = $trainingRuns;
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
     * @return int
     */
    public function getInputLength(): int
    {
        return $this->inputLength;
    }

    /**
     * @param int $inputLength
     */
    public function setInputLength(int $inputLength)
    {
        $this->inputLength = $inputLength;
    }

    /**
     * @return int
     */
    public function getOutputLength(): int
    {
        return $this->outputLength;
    }

    /**
     * @param int $outputLength
     */
    public function setOutputLength(int $outputLength)
    {
        $this->outputLength = $outputLength;
    }

    /**
     * @return Prediction[]|ArrayCollection
     */
    public function getPredictions()
    {
        return $this->predictions;
    }

    /**
     * @param Prediction[]|ArrayCollection $predictions
     */
    public function setPredictions($predictions)
    {
        $this->predictions = $predictions;
    }

    public function addPrediction(Prediction $prediction)
    {
        $this->predictions->add($prediction);
    }

    /**
     * @return float
     */
    public function getLearningRate()
    {
        return $this->learningRate;
    }

    /**
     * @param float $learningRate
     */
    public function setLearningRate(float $learningRate)
    {
        $this->learningRate = $learningRate;
    }

    /**
     * @return string
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }

    /**
     * @param string $valueType
     * @return $this
     */
    public function setValueType(string $valueType)
    {
        $this->valueType = $valueType;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutopilot(): bool
    {
        return $this->autopilot;
    }

    /**
     * @param bool $autopilot
     */
    public function setAutopilot(bool $autopilot)
    {
        $this->autopilot = $autopilot;
    }

    /**
     * @return float
     */
    public function getDirectionHitRatio(): float
    {
        return $this->directionHitRatio;
    }

    /**
     * @param float $directionHitRatio
     */
    public function setDirectionHitRatio($directionHitRatio)
    {
        $this->directionHitRatio = $directionHitRatio;
    }

    /**
     * @return int
     */
    public function getEpochsPerTrainingRun()
    {
        return $this->epochsPerTrainingRun;
    }

    /**
     * @param int $epochsPerTrainingRun
     */
    public function setEpochsPerTrainingRun(int $epochsPerTrainingRun)
    {
        $this->epochsPerTrainingRun = $epochsPerTrainingRun;
    }

    /**
     * @return float|null
     */
    public function getDropout()
    {
        return $this->dropout;
    }

    /**
     * @param float $dropout
     */
    public function setDropout($dropout)
    {
        $this->dropout = $dropout;
    }

    /**
     * @return bool
     */
    public function isBias(): bool
    {
        return $this->bias;
    }

    /**
     * @param bool $bias
     */
    public function setBias(bool $bias)
    {
        $this->bias = $bias;
    }

    /**
     * @return bool
     */
    public function isUseDropout(): bool
    {
        return $this->useDropout;
    }

    /**
     * @param bool $useDropout
     */
    public function setUseDropout(bool $useDropout)
    {
        $this->useDropout = $useDropout;
    }

    /**
     * @return string
     */
    public function getOptimizer(): string
    {
        return $this->optimizer;
    }

    /**
     * @param string $optimizer
     */
    public function setOptimizer(string $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param string $imagePath
     */
    public function setImagePath(string $imagePath)
    {
        $this->imagePath = $imagePath;
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
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return bool
     */
    public function isGenerateImage(): bool
    {
        return $this->generateImage;
    }

    /**
     * @param bool $generateImage
     */
    public function setGenerateImage(bool $generateImage)
    {
        $this->generateImage = $generateImage;
    }

    /**
     * @return bool
     */
    public function isSeparateInputSymbols(): bool
    {
        return $this->separateInputSymbols;
    }

    /**
     * @param bool $separateInputSymbols
     */
    public function setSeparateInputSymbols(bool $separateInputSymbols)
    {
        $this->separateInputSymbols = $separateInputSymbols;
    }

    /**
     * @return bool
     */
    public function isShuffleTrainingData(): bool
    {
        return $this->shuffleTrainingData;
    }

    /**
     * @param bool $shuffleTrainingData
     */
    public function setShuffleTrainingData(bool $shuffleTrainingData)
    {
        $this->shuffleTrainingData = $shuffleTrainingData;
    }

    /**
     * @return int
     */
    public function getBalanceHitsAndFails()
    {
        return $this->balanceHitsAndFails;
    }

    /**
     * @param int $balanceHitsAndFails
     */
    public function setBalanceHitsAndFails($balanceHitsAndFails)
    {
        $this->balanceHitsAndFails = $balanceHitsAndFails;
    }

    /**
     * @return bool
     */
    public function hasCustomShape(): bool
    {
        return $this->customShape;
    }

    /**
     * @param bool $customShape
     */
    public function setCustomShape(bool $customShape)
    {
        $this->customShape = $customShape;
    }

    /**
     * @return string
     */
    public function getShape(): string
    {
        return $this->shape;
    }

    /**
     * @param string $shape
     */
    public function setShape(string $shape = null)
    {
        if ($shape) {
            $this->shape = $shape;
        }
    }

    /**
     * @return bool
     */
    public function isUseMostActiveSymbols(): bool
    {
        return $this->useMostActiveSymbols;
    }

    /**
     * @param bool $useMostActiveSymbols
     */
    public function setUseMostActiveSymbols(bool $useMostActiveSymbols)
    {
        $this->useMostActiveSymbols = $useMostActiveSymbols;
    }

    /**
     * @return int
     */
    public function getMostActiveSymbolsCount()
    {
        return $this->mostActiveSymbolsCount;
    }

    /**
     * @param int $mostActiveSymbolsCount
     */
    public function setMostActiveSymbolsCount($mostActiveSymbolsCount)
    {
        $this->mostActiveSymbolsCount = $mostActiveSymbolsCount;
    }

    /**
     * @return bool
     */
    public function useOrderBooks(): bool
    {
        return $this->useOrderBooks;
    }

    /**
     * @param bool $useOrderBooks
     */
    public function setUseOrderBooks(bool $useOrderBooks)
    {
        $this->useOrderBooks = $useOrderBooks;
    }

    /**
     * @return int
     */
    public function getOrderBookSteps(): int
    {
        return $this->orderBookSteps;
    }

    /**
     * @param int $orderBookSteps
     */
    public function setOrderBookSteps($orderBookSteps)
    {
        $this->orderBookSteps = $orderBookSteps;
    }

    /**
     * @return TrainingRun
     */
    public function getLastTrainingRun()
    {
        $trainingRuns = $this->getTrainingRuns();
        $criteria = Criteria::create()
            ->orderBy(['createdAt' => 'DESC'])
            ->setMaxResults(1);

        return $trainingRuns->matching($criteria)->first();
    }

    /**
     * @return Prediction
     */
    public function getLastPrediction()
    {
        $predictions = $this->getPredictions();
        $criteria = Criteria::create()
            ->orderBy(['createdAt' => 'DESC'])
            ->setMaxResults(1);

        return $predictions->matching($criteria)->first();
    }
}
