<?php

namespace FeedBundle\Message;

class TrainNetworkMessage extends CreateNetworkMessage
{
    /**
     * @var string
     */
    private $trainingDataFile;

    /**
     * @var string
     */
    private $trainingRunId;

    /**
     * @var integer
     */
    private $epochs;

    /**
     * @var bool
     */
    private $generateImage;

    /**
     * @return string
     */
    public function getTrainingDataFile(): string
    {
        return $this->trainingDataFile;
    }

    /**
     * @param string $trainingDataFile
     */
    public function setTrainingDataFile(string $trainingDataFile)
    {
        $this->trainingDataFile = $trainingDataFile;
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
    public function setTrainingRunId(string $trainingRunId = null)
    {
        $this->trainingRunId = $trainingRunId;
    }

    /**
     * @return int
     */
    public function getEpochs(): int
    {
        return $this->epochs;
    }

    /**
     * @param int $epochs
     */
    public function setEpochs(int $epochs)
    {
        $this->epochs = $epochs;
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
}
