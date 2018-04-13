<?php

namespace Tests\OutputType;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\SourceInput;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\TradeData;
use DataModelBundle\OutputType\HasRaisedByOutputType;
use Doctrine\Common\Collections\ArrayCollection;

class HasRaisedByOutputTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HasRaisedByOutputType
     */
    private $outputType;

    public function setUp()
    {
        $this->outputType = new HasRaisedByOutputType();
    }
    public function testCalculateOutputData()
    {
        $outputConfig = (new OutputConfig())->setNetwork((new Network())->setValueType(Network::VALUE_TYPE_ABSOLUTE));
        $outputConfig->setThresholdPercentage(20.00);
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
        $this->assertEquals(0, $result[0][0]);
        $this->assertEquals(1, $result[0][1]);
        $this->assertEquals(1, $result[1][0]);
        $this->assertEquals(0, $result[1][1]);
        $sourceInput->setTradesData($this->getTradesData([1.0, 1.19, 1.45, 1.0, 1.0, 1.21]));
        $result = $this->outputType->getOutputData($outputConfig, $networkData);
        $this->assertEquals(1, $result[0][0]);
        $this->assertEquals(0, $result[0][1]);
        $this->assertEquals(0, $result[1][0]);
        $this->assertEquals(1, $result[1][1]);
        $this->assertEquals(1, $result[2][0]);
        $this->assertEquals(0, $result[2][1]);
        $this->assertEquals(1, $result[3][0]);
        $this->assertEquals(0, $result[3][1]);
        $this->assertEquals(0, $result[4][0]);
        $this->assertEquals(1, $result[4][1]);
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

    public function testExceptionIfNoThresholdSet()
    {
        $this->expectException(\Exception::class);
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
        $this->outputType->getOutputData($outputConfig, $networkData);
    }
}
