<?php

namespace FeedBundle\Message;

class RequestPredictionMessage
{
    /**
     * @var string
     */
    private $predictionId;

    /**
     * @return string
     */
    public function getPredictionId()
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
}
