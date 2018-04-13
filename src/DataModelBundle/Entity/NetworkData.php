<?php

namespace DataModelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

class NetworkData
{
    /**
     * @var ArrayCollection | SourceInput[]
     * @Groups({"network-create-preview"})
     */
    private $sourceInputs;

    /**
     * @var TrainingData
     * @Groups({"network-create-preview"})
     */
    private $trainingData;

    public function __construct()
    {
        $this->sourceInputs = new ArrayCollection();
        $this->trainingData = new TrainingData();
    }

    /**
     * @return ArrayCollection|SourceInput[]
     */
    public function getSourceInputs(): ArrayCollection
    {
        return $this->sourceInputs;
    }

    /**
     * @param array|ArrayCollection $sourceInputs
     */
    public function setSourceInputs(ArrayCollection $sourceInputs)
    {
        $this->sourceInputs = $sourceInputs;
    }

    /**
     * @return TrainingData
     */
    public function getTrainingData():TrainingData
    {
        return $this->trainingData;
    }

    /**
     * @param TrainingData $trainingData
     */
    public function setTrainingData(TrainingData $trainingData)
    {
        $this->trainingData = $trainingData;
    }

    public function addSourceInput(SourceInput $sourceInput)
    {
        $this->sourceInputs->add($sourceInput);
    }
}
