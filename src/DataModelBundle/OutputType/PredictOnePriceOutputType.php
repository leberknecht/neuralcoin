<?php

namespace DataModelBundle\OutputType;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Trade;
use NetworkBundle\Service\NetworkDataService;
use Doctrine\Common\Collections\ArrayCollection;

class PredictOnePriceOutputType extends BaseOutputType
{
    /**
     * @param OutputConfig $outputConfig
     * @param Trade[]|ArrayCollection $trades
     * @param Trade[]|ArrayCollection $sourceTrades
     * @return array
     */
    protected function calculateOutputData(OutputConfig $outputConfig, $trades, $sourceTrades): array
    {
        $outputData = [];

        for ($x = 0; $x < count($trades); $x++) {
            /** @var Trade $trade */
            $trade = $trades->current();
            $currentTrade = $trade;
            $previousTrade = $sourceTrades[$x];

            switch($outputConfig->getNetwork()->getValueType()) {
                case Network::VALUE_TYPE_ABSOLUTE:
                    $outputData[] = [$trade->getPrice()];
                    break;
                case Network::VALUE_TYPE_PERCENTAGE:
                    if ($previousTrade->getPrice() == 0) {
                        $outputData[] = [100];
                    } else {
                        $outputData[] = [((100 / $previousTrade->getPrice()) * $currentTrade->getPrice()) - 100];
                    }
                    break;
                case Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED:
                    if ($previousTrade->getPrice() == 0) {
                        $outputData[] = [100];
                    } else {
                        $change = ((100 / $previousTrade->getPrice()) * $currentTrade->getPrice()) - 100;
                        $outputData[] = [($x > 0 ? $outputData[$x - 1][0] : 0) + $change];
                    }
                    break;
            }

            $trades->next();
        }

        return $outputData;
    }

    public function evaluatePrediction(Prediction $prediction, Trade $predictedTrade)
    {
        $prediction->setActualPrice($predictedTrade->getPrice());

        /**
         * @todo better error-state handling here
         */
        if (null == $prediction->getPriceAtPrediction()) {
            return;
        }
        $actualChange =  ((100 / $prediction->getPriceAtPrediction()) * $prediction->getActualPrice()) - 100;
        $prediction->setActualChange($actualChange);

        $raisePredicted = $prediction->getPriceAtPrediction() < $prediction->getPredictedValue();
        if (
            ($raisePredicted && $prediction->getPriceAtPrediction() < $prediction->getActualPrice()) ||
            (!$raisePredicted && $prediction->getPriceAtPrediction() > $prediction->getActualPrice())
        ) {
            $prediction->setDirectionHit(true);
        } else {
            $prediction->setDirectionHit(false);
        }
    }

    public function setPredictedPrice(NetworkData $networkData, Prediction $prediction, $max, $min)
    {
        $predictedValue = $prediction->getOutputData()[0];

        if (Network::VALUE_TYPE_ABSOLUTE == $prediction->getNetwork()->getValueType()) {
            $predictedPrice = $predictedValue;
        } else {
            $predictedPrice = $prediction->getPriceAtPrediction() + ( ($prediction->getPriceAtPrediction()  / 100 ) * $predictedValue);
        }
        $prediction->setPredictedValue($predictedPrice);
        $predictedChange = ((100 / $prediction->getPriceAtPrediction()) * $prediction->getPredictedValue()) - 100;
        $prediction->setPredictedChange($predictedChange);
    }
}
