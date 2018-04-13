<?php

namespace FeedBundle\Message;

class PredictionMessage extends CreateNetworkMessage
{
    /** @var  string */
    private $predictionId;

    /** @var  array */
    private $inputData;

    /**
     * @return string
     */
    public function getPredictionId(): string
    {
        return $this->predictionId;
    }

    /**
     * @param string $predictionId
     */
    public function setPredictionId($predictionId)
    {
        $this->predictionId = $predictionId;
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
}
