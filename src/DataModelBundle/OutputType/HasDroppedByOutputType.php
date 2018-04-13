<?php

namespace DataModelBundle\OutputType;

use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Trade;
use NetworkBundle\Service\NetworkDataService;
use Doctrine\Common\Collections\ArrayCollection;

class HasDroppedByOutputType extends BoolOutputType
{
    /**
     * @param OutputConfig $outputConfig
     * @param Trade[]|ArrayCollection $trades
     * @param Trade[]|ArrayCollection $sourceTrades
     * @return array
     * @throws \Exception
     */
    protected function calculateOutputData(OutputConfig $outputConfig, $trades, $sourceTrades): array
    {
        return $this->calculateBoolOutputData($outputConfig, $trades, $sourceTrades, false);
    }

    public function evaluatePrediction(Prediction $prediction, Trade $predictedTrade)
    {
        $this->evaluateBoolPrediction($prediction, $predictedTrade, false);
    }

    public function setPredictedPrice(NetworkData $networkData, Prediction $prediction, $max, $min)
    {
        $this->setPredictedBoolPrice($prediction);
    }
}
