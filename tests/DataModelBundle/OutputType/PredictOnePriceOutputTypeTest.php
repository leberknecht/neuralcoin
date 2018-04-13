<?php

namespace Tests\OutputType;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\SourceInput;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\TradeData;
use DataModelBundle\OutputType\PredictOnePriceOutputType;
use Doctrine\Common\Collections\ArrayCollection;

class PredictOnePriceOutputTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PredictOnePriceOutputType */
    private $outputType;

    public function setUp()
    {
        $this->outputType = new PredictOnePriceOutputType();
    }
    public function testGetOutputDataTargetNotFoundInInputs()
    {
        $outputConfig = (new OutputConfig())->setNetwork((new Network())->setValueType(Network::VALUE_TYPE_PERCENTAGE));
        $networkData = new NetworkData();

        $symbol = new Symbol();
        $symbol->setId(42);
        $outputConfig->setPricePredictionSymbol($symbol);

        $sourceInput = new SourceInput();
        $sourceInput->setSymbolId(23);
        $networkData->setSourceInputs(new ArrayCollection([$sourceInput]));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('target input "42" not found in source-inputs');
        $this->outputType->getOutputData($outputConfig, $networkData);
    }

    public function testGetOutputFetchCorrectInput()
    {
        $outputConfig = (new OutputConfig())->setNetwork((new Network())->setValueType(Network::VALUE_TYPE_PERCENTAGE));
        $networkData = new NetworkData();

        $symbol = new Symbol();
        $symbol->setId(42);
        $outputConfig->setPricePredictionSymbol($symbol);

        $sourceInput1 = new SourceInput();
        $sourceInput1->setSymbolId(1);
        $sourceInput2 = new SourceInput();
        $sourceInput2->setSymbolId(42);
        $sourceInput3 = new SourceInput();
        $sourceInput3->setSymbolId(2);
        $networkData->setSourceInputs(new ArrayCollection([$sourceInput1, $sourceInput2, $sourceInput3]));
        $this->outputType->getOutputData($outputConfig, $networkData);
    }

    public function testGetOutputDataAbsolute()
    {
        $outputConfig = (new OutputConfig())->setNetwork((new Network())->setValueType(Network::VALUE_TYPE_ABSOLUTE));
        $networkData = new NetworkData();

        $symbol = new Symbol();
        $symbol->setId(42);
        $outputConfig->setPricePredictionSymbol($symbol);
        $outputConfig->setStepsAhead(1);

        $sourceInput = new SourceInput();
        $sourceInput->setSymbolId(42);
        $sourceInput->setTradesData($this->getTradesData([0.5, 0.75, 0.8]));
        $networkData->setSourceInputs(new ArrayCollection([$sourceInput]));

        $result = $this->outputType->getOutputData($outputConfig, $networkData);
        $this->assertEquals(0.75, $result[0][0]);
        $this->assertEquals(0.8, $result[1][0]);
    }

    /**
     * @param array $prices
     * @param array $expectedPercentageValue
     * @dataProvider percentageTestData
     */
    public function testGetOutputDataPercentage(array $prices, array $expectedPercentageValue)
    {
        $outputConfig = (new OutputConfig())->setNetwork((new Network())->setValueType(Network::VALUE_TYPE_PERCENTAGE));
        $networkData = new NetworkData();

        $symbol = new Symbol();
        $symbol->setId(42);
        $outputConfig->setPricePredictionSymbol($symbol);
        $outputConfig->setStepsAhead(1);

        $sourceInput = new SourceInput();
        $sourceInput->setSymbolId(42);
        $sourceInput->setTradesData($this->getTradesData($prices));
        $networkData->setSourceInputs(new ArrayCollection([$sourceInput]));

        $result = $this->outputType->getOutputData($outputConfig, $networkData);
        for ($x = 0; $x < count($expectedPercentageValue); $x++) {
            $this->assertEquals($expectedPercentageValue[$x], round($result[$x][0], 2));
        }
    }

    public function percentageTestData()
    {
        return [
           [ [0.5, 0.75, 0.25], [-50] ], //0.5 to 0.25
           [ [100, 120, 180, 80], [80, -33.33] ],
        ];
    }

    /**
     * @param array $prices
     * @return ArrayCollection
     */
    private function getTradesData(array $prices)
    {
        $result = new ArrayCollection();
        foreach ($prices as $price) {
            $result->add((new TradeData())->setPrice($price));
        }

        return $result;
    }
}
