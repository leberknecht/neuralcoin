<?php

namespace Tests\NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\SourceInput;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Entity\TrainingData;
use DataModelBundle\Repository\PredictionRepository;
use DataModelBundle\Repository\TradeRepository;
use DataModelBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use NetworkBundle\Service\NetworkDataService;
use NetworkBundle\Service\PredictionService;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;
use Tests\Functional\Fixtures\Service\FakeGetPredictionProducer;
use Tests\Functional\Fixtures\Service\FakeRequestPredictionProducer;

class PredictionServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PredictionService */
    private $predictionService;
    /** @var FakeGetPredictionProducer | PHPUnit_Framework_MockObject_MockObject */
    private $rpcClientMock;
    /** @var NetworkDataService | PHPUnit_Framework_MockObject_MockObject */
    private $networkDataRepoMock;
    /** @var  SerializerService | PHPUnit_Framework_MockObject_MockObject */
    private $serializerServiceMock;
    /** @var  EntityManager | PHPUnit_Framework_MockObject_MockObject */
    private $emMock;
    /** @var  TradeRepository | PHPUnit_Framework_MockObject_MockObject */
    private $tradeRepoMock;
    /** @var  PredictionRepository | PHPUnit_Framework_MockObject_MockObject */
    private $predictionRepoMock;
    /** @var FakeRequestPredictionProducer | PHPUnit_Framework_MockObject_MockObject */
    private $predictionProducerMock;

    public function setUp()
    {
        $this->rpcClientMock = $this->getMockBuilder(FakeGetPredictionProducer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerServiceMock = $this->getMockBuilder(SerializerService::class)->disableOriginalConstructor()->getMock();
        $this->networkDataRepoMock = $this->getMockBuilder(NetworkDataService::class)->disableOriginalConstructor()->getMock();
        $this->tradeRepoMock = $this->getMockBuilder(TradeRepository::class)->disableOriginalConstructor()->getMock();
        $this->predictionRepoMock = $this->getMockBuilder(PredictionRepository::class)->disableOriginalConstructor()->getMock();
        $this->predictionProducerMock = $this->getMockBuilder(FakeRequestPredictionProducer::class)->disableOriginalConstructor()->getMock();
        $this->predictionService = new PredictionService(
            $this->rpcClientMock,
            $this->serializerServiceMock,
            $this->networkDataRepoMock,
            $this->tradeRepoMock,
            $this->predictionProducerMock,
            $this->predictionRepoMock
        );
        $this->emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->predictionService->setEntityManager($this->emMock);
        $this->predictionService->setLogger(new NullLogger());

        $this->predictionRepoMock->expects($this->any())
            ->method('findDirectionHitRatio')
            ->willReturn(42.00);
    }

    public function testRequestPrediction()
    {
        $network = new Network();
        $this->predictionProducerMock->expects($this->once())
            ->method('publish');
        $actual = $this->predictionService->requestPrediction($network);
        $this->assertInstanceOf(Prediction::class, $actual);
    }

    public function testGetPrediction()
    {
        $network = new Network();
        $symbol = new Symbol();
        $symbol->setId('testsymbol');
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol($symbol));
        $network->setInputLength(2);
        $network->setOutputLength(1);

        $this->tradeRepoMock->expects($this->once())
            ->method('findLastPrice')
            ->willReturn(0.42);

        $networkData = new NetworkData();
        /** @var TrainingData | PHPUnit_Framework_MockObject_MockObject $trainingData */
        $trainingData = $this->getMockBuilder(TrainingData::class)->getMock();
        $trainingData->expects($this->once())
            ->method('getInputs')
            ->willReturn([ [ [0.5] ], [ [0.75] ] ]);
        $networkData->setTrainingData($trainingData);

        $sourceInput = new SourceInput();
        $sourceInput->setSymbolId($symbol->getId());
        $sourceInput->setMaxPrice(0.96);
        $sourceInput->setMinPrice(0.30);
        $networkData->setSourceInputs(new ArrayCollection([
            $sourceInput
        ]));

        $this->networkDataRepoMock->expects($this->once())
            ->method('getPredictionData')
            ->willReturn($networkData);
        $stdClass = new \stdClass();
        $stdClass->outputs = [0.23];
        $this->rpcClientMock->expects($this->once())
            ->method('getReplies')
            ->willReturn([$stdClass]);
        $actual = $this->predictionService->getPrediction((new Prediction())->setNetwork($network));
        $this->assertEquals(-45.238095238095, $actual->getPredictedChange());
        $this->assertInstanceOf(Prediction::class, $actual);
    }

    public function testGetPredictionPercentageOutputNormalizedInput()
    {
        $network = new Network();
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol(new Symbol()));
        $network->setInputLength(2);
        $network->setOutputLength(1);
        $this->tradeRepoMock->expects($this->once())
            ->method('findLastPrice')
            ->willReturn(1119.20);

        $networkData = new NetworkData();
        /** @var TrainingData | PHPUnit_Framework_MockObject_MockObject $trainingData */
        $trainingData = $this->getMockBuilder(TrainingData::class)->getMock();
        $trainingData->expects($this->once())
            ->method('getInputs')
            ->willReturn([ [ [0.5] ], [ [0.75] ] ]);
        $networkData->setSourceInputs(new ArrayCollection([
            (new SourceInput())->setMaxPrice(1192.68)->setMinPrice(1190.60)
        ]));
        $networkData->setTrainingData($trainingData);

        $this->networkDataRepoMock->expects($this->once())
            ->method('getPredictionData')
            ->willReturn($networkData);
        $stdClass = new \stdClass();
        $stdClass->outputs = [20];
        $this->rpcClientMock->expects($this->once())
            ->method('getReplies')
            ->willReturn([$stdClass]);
        $actual = $this->predictionService->getPrediction((new Prediction())->setNetwork($network));
        $this->assertEquals(20, $actual->getPredictedValue());
    }

    public function testGetPredictionExceptionOnEmptyReply()
    {
        $network = new Network();
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol(new Symbol()));
        $network->setInputLength(2);
        $network->setOutputLength(1);
        $this->tradeRepoMock->expects($this->once())
            ->method('findLastPrice')
            ->willReturn(0.42);
        $networkData = new NetworkData();
        /** @var TrainingData | PHPUnit_Framework_MockObject_MockObject $trainingData */
        $trainingData = $this->getMockBuilder(TrainingData::class)->getMock();
        $trainingData->expects($this->once())
            ->method('getInputs')
            ->willReturn([ [ [0.5] ], [ [0.75] ] ]);
        $networkData->setTrainingData($trainingData);

        $this->networkDataRepoMock->expects($this->once())
            ->method('getPredictionData')
            ->willReturn($networkData);
        $stdClass = new \stdClass();
        $stdClass->outputs = [23];
        $this->rpcClientMock->expects($this->once())
            ->method('getReplies')
            ->willReturn(null);
        $this->expectException(\Exception::class);
        $this->predictionService->getPrediction((new Prediction())->setNetwork($network));
    }

    public function testCheckPredictionNotFinishedYet()
    {
        $prediction = new Prediction();
        $network = new Network();
        $prediction->setPriceAtPrediction(42.00);
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol(new Symbol()));
        $prediction->setNetwork($network);

        $this->tradeRepoMock->expects($this->once())
            ->method('findPredictedTrade')->willReturn(null);
        $this->predictionService->checkPrediction($prediction);
        $this->assertFalse($prediction->isFinished());
    }

    public function testCheckPredictionFinished()
    {
        $prediction = new Prediction();
        $network = new Network();
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol(new Symbol()));
        $prediction->setNetwork($network);
        $prediction->setPriceAtPrediction(42.00);
        $prediction->setCreatedAt(new \DateTime());


        $trade = new Trade();
        $trade->setTime(new \DateTime());
        $this->tradeRepoMock->expects($this->once())
            ->method('findPredictedTrade')->willReturn($trade);
        $this->predictionService->checkPrediction($prediction);
        $this->assertTrue($prediction->isFinished());
        $this->assertEquals(42, $network->getDirectionHitRatio());
    }

    /**
     * @param $predictedPrice
     * @param $priceAtPredication
     * @param $actualPrice
     * @param $expectedActualChange
     * @dataProvider calculateChangeDataProvider
     */
    public function testCalculateChanges(
        $predictedPrice, $priceAtPredication, $actualPrice, $expectedActualChange
    )
    {
        $prediction = new Prediction();
        $prediction->setCreatedAt(new \DateTime());
        $network = new Network();
        $network->setOutputConfig((new OutputConfig())->setPricePredictionSymbol(new Symbol()));
        $prediction->setNetwork($network);

        $trade = new Trade();
        $trade->setTime(new \DateTime());
        $trade->setCreatedAt(new \DateTime());
        $this->tradeRepoMock->expects($this->once())
            ->method('findPredictedTrade')->willReturn($trade);
        $prediction->setPriceAtPrediction($priceAtPredication);
        $prediction->setPredictedValue($predictedPrice);
        $trade->setPrice($actualPrice);
        $this->predictionService->checkPrediction($prediction);
        $this->assertEquals($expectedActualChange, $prediction->getActualChange());
        $this->assertTrue($prediction->isFinished());
    }

    public function calculateChangeDataProvider()
    {
        return [
            [76, 42.00, 76.00, 80.952380952381],
            [76, 42.00, 80.00, 90.476190476190482],
            [28, 42.00, 30, -28.571428571429],
        ];
    }

}