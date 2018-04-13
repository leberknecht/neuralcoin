<?php


namespace DataModelBundle\Entity;


use DataModelBundle\Exception\InsufficientTrainingDataException;
use Symfony\Component\Serializer\Annotation\Groups;

class TrainingData
{
    /**
     * @var array
     */
    private $inputs;

    /**
     * @var array
     */
    private $outputs;

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    public function addInputSet(array $inputSet)
    {
        $this->inputs[] = $inputSet;
    }

    public function setInputs(array $inputs)
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @param array $outputs
     */
    public function setOutputs(array $outputs)
    {
        $this->outputs = $outputs;
    }

    /**
     * @return array
     * @throws \Exception
     * @Groups({"network-create-preview"})
     */
    public function getRawData()
    {
        if (empty($this->outputs) || empty($this->inputs)) {
            throw new InsufficientTrainingDataException('in- or outputs empty');
        }
        $this->inputs = array_slice($this->inputs, 0, count($this->outputs));
        $this->outputs = array_slice($this->outputs, 0, count($this->inputs));

        $trainingData = [];
        for ($x = 0; $x < count($this->outputs); $x++) {
            $trainingData[] = [$this->inputs[$x], $this->outputs[$x]];
        }

        return $trainingData;
    }
}
