<?php

namespace FeedBundle\Message;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;

class CreateNetworkMessage
{
    /** @var  int */
    private $numberOfHiddenLayers;
    /** @var  string */
    private $activationFunction;
    /** @var  string */
    private $networkFilePath;
    /** @var  int */
    private $inputLength;
    /** @var  int */
    private $outputLength;
    /** @var  boolean */
    private $classify = false;
    /** @var  float */
    private $learningRate;
    /** @var  boolean */
    private $useDropout;
    /** @var  float */
    private $dropout = 0.0;
    /** @var  boolean */
    private $bias;
    /** @var  string */
    private $optimizer;
    /** @var  string */
    private $customShape = false;
    /** @var  array */
    private $shape = [];

    /**
     * @return int
     */
    public function getNumberOfHiddenLayers(): int
    {
        return $this->numberOfHiddenLayers;
    }

    /**
     * @param int $numberOfHiddenLayers
     */
    public function setNumberOfHiddenLayers(int $numberOfHiddenLayers)
    {
        $this->numberOfHiddenLayers = $numberOfHiddenLayers;
    }

    /**
     * @return string
     */
    public function getActivationFunction(): string
    {
        return $this->activationFunction;
    }

    /**
     * @param string $activationFunction
     */
    public function setActivationFunction(string $activationFunction)
    {
        $this->activationFunction = $activationFunction;
    }

    /**
     * @return string
     */
    public function getNetworkFilePath(): string
    {
        return $this->networkFilePath;
    }

    /**
     * @param string $networkFilePath
     */
    public function setNetworkFilePath($networkFilePath)
    {
        $this->networkFilePath = $networkFilePath;
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
     * @return boolean
     */
    public function isClassify()
    {
        return $this->classify;
    }

    /**
     * @param boolean $classify
     */
    public function setClassify($classify)
    {
        $this->classify = $classify;
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
    public function setLearningRate($learningRate)
    {
        $this->learningRate = $learningRate;
    }

    /**
     * @return bool
     */
    public function isUseDropout()
    {
        return $this->useDropout;
    }

    /**
     * @param bool $useDropout
     */
    public function setUseDropout($useDropout)
    {
        $this->useDropout = $useDropout;
    }

    /**
     * @return float
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
    public function getCustomShape(): string
    {
        return $this->customShape;
    }

    /**
     * @param string $customShape
     */
    public function setCustomShape(string $customShape)
    {
        $this->customShape = $customShape;
    }

    /**
     * @return array
     */
    public function getShape()
    {
        return $this->shape;
    }

    /**
     * @param array $shape
     */
    public function setShape($shape)
    {
        $this->shape = $shape;
    }

    /**
     * @param Network $network
     */
    public function setFromNetwork(Network $network)
    {
        $this->setActivationFunction($network->getActivationFunction());
        $this->setInputLength($network->getInputLength());
        $this->setNumberOfHiddenLayers($network->getHiddenLayers());
        $this->setOutputLength($network->getOutputLength());
        $this->setNetworkFilePath($network->getFilePath());
        $this->setOptimizer($network->getOptimizer());
        $this->setLearningRate($network->getLearningRate());
        $this->setUseDropout($network->isUseDropout());
        $this->setDropout($network->getDropout());
        $this->setBias($network->isBias());
        $this->setCustomShape($network->hasCustomShape());
        $this->setShape(json_decode($network->getShape()));
        if (in_array($network->getOutputConfig()->getType(), [
            OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY,
            OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY,
        ])) {
            $this->setClassify(true);
        } else {
            $this->setClassify(false);
        }
    }
}
