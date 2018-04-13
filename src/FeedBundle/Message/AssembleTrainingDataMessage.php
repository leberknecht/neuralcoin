<?php

namespace FeedBundle\Message;

class AssembleTrainingDataMessage
{
    /**
     * @var string
     */
    private $networkId;

    /**
     * @var string
     */
    private $trainingRunId;

    /**
     * @return string
     */
    public function getNetworkId(): string
    {
        return $this->networkId;
    }

    /**
     * @param string $networkId
     */
    public function setNetworkId(string $networkId)
    {
        $this->networkId = $networkId;
    }

    /**
     * @return string
     */
    public function getTrainingRunId(): string
    {
        return $this->trainingRunId;
    }

    /**
     * @param string $trainingRunId
     */
    public function setTrainingRunId($trainingRunId)
    {
        $this->trainingRunId = $trainingRunId;
    }
}
