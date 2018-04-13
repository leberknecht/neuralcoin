<?php

namespace Tests\NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Repository\OrderBookRepository;
use DataModelBundle\Repository\SymbolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use NetworkBundle\Service\NetworkDataService;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;

class NetworkDataServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NetworkDataService
     */
    private $networkDataService;
    /**
     * @var SymbolRepository | PHPUnit_Framework_MockObject_MockObject
     */
    private $symbolRepoMock;

    /**
     * @var OrderBookRepository | PHPUnit_Framework_MockObject_MockObject
     */
    private $orderBookRepoMock;

    public function setUp()
    {
        $this->symbolRepoMock = $this->createMock(SymbolRepository::class);
        $this->orderBookRepoMock = $this->createMock(OrderBookRepository::class);
        $this->networkDataService = new NetworkDataService(
            $this->symbolRepoMock,
            $this->orderBookRepoMock
        );
        $this->networkDataService->setLogger(new NullLogger());
    }

    public function testPrepareTrainingInputDataNoSymbolsException()
    {
        $network = new Network();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no symbols specified');
        $this->networkDataService->getNetworkData($network);
    }

    public function testPrepareTrainingInputDataNoTradesException()
    {
        $network = new Network();
        $this->symbolRepoMock->expects($this->once())
            ->method('getSymbolsForTraining')
            ->willReturn([
                (new Symbol())
            ]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('symbol has no trades attached');
        $this->networkDataService->getNetworkData($network);
    }

    public function testPrepareTrainingInputDataNPreview()
    {
        $network = new Network();
        $this->symbolRepoMock->expects($this->once())
            ->method('getSymbolsPreview')
            ->willReturn([
                (new Symbol())
            ]);
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('symbol has no trades attached');
        $this->networkDataService->getNetworkData($network, true);
    }

    public function testPrepareTrainingInputDataNoOutputException()
    {
        $network = new Network();
        $symbol = new Symbol();
        $symbol->setName('test');
        $symbol->setId(23);
        $symbol->setTrades(new ArrayCollection([
            (new Trade())->setId(42)->setSymbol($symbol)->setTime(new \DateTime())
        ]));
        $this->symbolRepoMock->expects($this->once())
            ->method('getSymbolsForTraining')
            ->willReturn([
                $symbol
            ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no output configured');
        $this->networkDataService->getNetworkData($network);
    }

    public function testShuffleNetworkData()
    {
        $network = new Network();
        $symbol = new Symbol();
        $outputConfig = new OutputConfig();
        $trades = new ArrayCollection();

        $symbol->setName('test');
        $symbol->setId(23);

        $prices = [100, 103, 104, 90, 100];
        for ($x = 0; $x < count($prices); $x++) {
            $trades->add((new Trade())->setId($x)->setSymbol($symbol)->setTime(new \DateTime())->setPrice($prices[$x]));
        }

        $symbol->setTrades($trades);
        $outputConfig->setId(23)->setPricePredictionSymbol($symbol);
        $outputConfig->setStepsAhead(1);
        $outputConfig->setType(OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY);
        $outputConfig->setThresholdPercentage(2);

        $network->setOutputConfig($outputConfig);
        $this->symbolRepoMock->expects($this->any())
            ->method('getSymbolsForTraining')
            ->willReturn([
                $symbol
            ]);

        $data = $this->networkDataService->getNetworkData($network);
        $this->assertEquals([
            [ [100], [0,1]],
            [ [103], [1,0]],
            [ [104], [1,0]],
            [ [90], [0,1]],
        ], $data->getTrainingData()->getRawData());
        $network->setShuffleTrainingData(true);
        $data = $this->networkDataService->getNetworkData($network)->getTrainingData()->getRawData();
        $wrongOrder = [ 100, 103, 104, 90];
        $shuffled = false;
        $wrongOutput = false;
        foreach($wrongOrder as $index => $price) {
            if($data[$index][0] != $price) {
                $shuffled = true;
                switch (true) {
                    case $data[$index][0] == 100 && $data[$index][1] != [0,1]:
                    case $data[$index][0] == 103 && $data[$index][1] != [1,0]:
                    case $data[$index][0] == 104 && $data[$index][1] != [1,0]:
                    case $data[$index][0] == 90 && $data[$index][1] != [0,1]:
                        $wrongOutput = true;
                        break;
                }
            }
        }
        $this->assertTrue($shuffled);
        $this->assertFalse($wrongOutput);
    }

    public function prepareTrainingDataProvider()
    {
        return [
            [ [0.5, 0.75],              [ [ [0.5], [0.75] ] ],                      Network::VALUE_TYPE_ABSOLUTE ],
            [ [0.5, 0.75, 1.0],         [ [ [0.5], [0.75] ], [ [0.75], [1.0] ] ],   Network::VALUE_TYPE_ABSOLUTE ],

            [ [0.2, 0.4, 0.5, 0.4],    [ [ [100], [150] ], [ [25], [0] ] ],         Network::VALUE_TYPE_PERCENTAGE ],
            [ [0.2, 0.4, 0.5, 0.2 ],   [ [ [100], [150] ], [ [25], [-50] ] ],       Network::VALUE_TYPE_PERCENTAGE ],
            [ [0.2, 0.4, 0.5, 0.2 ],   [ [ [100], [150] ], [ [125], [100] ] ],      Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED ],

        ];
    }

    /**
     * @param array $prices
     * @param array $rawData
     * @param string $valueType
     * @dataProvider prepareTrainingDataProvider
     */
    public function testPrepareTrainingInputData(array $prices, array $rawData, $valueType)
    {
        $network = new Network();
        $symbol = new Symbol();
        $outputConfig = new OutputConfig();
        $network->setValueType($valueType);

        $symbol->setName('test');
        $symbol->setId(23);
        $trades = new ArrayCollection();

        for ($x = 0; $x < count($prices); $x++) {
            $trades->add((new Trade())->setId($x)->setSymbol($symbol)->setTime(new \DateTime())->setPrice($prices[$x]));
        }

        $symbol->setTrades($trades);
        $outputConfig->setId(23)->setPricePredictionSymbol($symbol);
        $outputConfig->setStepsAhead(1);
        $network->setOutputConfig($outputConfig);
        $this->symbolRepoMock->expects($this->once())
            ->method('getSymbolsForTraining')
            ->willReturn([
                $symbol
            ]);

        $data = $this->networkDataService->getNetworkData($network);
        $this->assertEquals($rawData, $data->getTrainingData()->getRawData());
    }

    public function testWithInterpolation()
    {
        $network = new Network();
        $symbol = new Symbol();
        $outputConfig = new OutputConfig();

        $symbol->setName('test');
        $symbol->setId(23);
        $trades = new ArrayCollection();

        $trades->add((new Trade())->setId(42)->setSymbol($symbol)->setTime(new \DateTime())->setPrice(23.42));

        $symbol->setTrades($trades);
        $outputConfig->setId(23)->setPricePredictionSymbol($symbol);
        $outputConfig->setStepsAhead(1);
        $network->setOutputConfig($outputConfig);
        $this->symbolRepoMock->expects($this->once())->method('getSymbolsForTraining')->willReturn([$symbol]);
        $this->symbolRepoMock->expects($this->once())->method('getSymbolsPreview')->willReturn([$symbol]);
        $network->setInterpolateInputs(true);
        $actual = $this->networkDataService->prepareSymbols($network);
        $this->assertCount(1, $actual);
        $actual = $this->networkDataService->prepareSymbols($network, true);
        $this->assertCount(1, $actual);
    }
}
