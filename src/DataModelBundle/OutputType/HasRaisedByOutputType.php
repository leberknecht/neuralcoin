<?php

namespace DataModelBundle\OutputType;

use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Trade;
use NetworkBundle\Service\NetworkDataService;
use Doctrine\Common\Collections\ArrayCollection;

class HasRaisedByOutputType extends BaseOutputType
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
        $outputData = [];
        if (empty($outputConfig->getThresholdPercentage())) {
            throw new \Exception('no raise-threshold specified');
        }

        for ($x = 0; $x < count($trades); $x++) {
            /** @var Trade $trade */
            $trade = $trades->current();

            $currentTrade = $trade;
            $previousTrade = $sourceTrades[$x];
            if ($previousTrade->getPrice() == 0) {
                $previousTrade->setPrice(0.000000001);
            }
            $percentageChange = ((100 / $previousTrade->getPrice()) * $currentTrade->getPrice()) - 100;
            if ($percentageChange > $outputConfig->getThresholdPercentage()) {
                $outputData[] = [0,1];
            } else {
                $outputData[] = [1,0];
            }
            $trades->next();
        }

        return $outputData;
    }

    public function evaluatePrediction(Prediction $prediction, Trade $predictedTrade)
    {
        $prediction->setActualPrice($predictedTrade->getPrice());

        if (null == $prediction->getPriceAtPrediction()) {
            return;
        }

        $actualChange =  ((100 / $prediction->getPriceAtPrediction()) * $prediction->getActualPrice()) - 100;
        $prediction->setActualChange($actualChange);

        $threshold = $prediction->getNetwork()->getOutputConfig()->getThresholdPercentage();
        if (empty($threshold)) {
            throw new \Exception('no threshold specified for has-raised-by, network: ' .
                $prediction->getNetwork()->getId()
            );
        }

        if ($prediction->getPredictedValue() > 0.5) {
            if ($actualChange >= $threshold) {
                $prediction->setDirectionHit(true);
            } else {
                $prediction->setDirectionHit(false);
            }
        }
    }

    public function setPredictedPrice(NetworkData $networkData, Prediction $prediction, $max, $min)
    {
        if ($prediction->getOutputData()[1] > 0.5) {
            $prediction->setPredictedChange($prediction->getNetwork()->getOutputConfig()->getThresholdPercentage());
        }
        $prediction->setPredictedValue($prediction->getOutputData()[1]);
    }
}
