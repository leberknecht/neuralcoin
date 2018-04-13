<?php
namespace Tests\FeedBundle\Service;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Repository\TradeRepository;
use Doctrine\ORM\EntityManager;
use FeedBundle\Service\QuoteManagerService;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\CacheItem;

class QuoteManagerServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var  QuoteManagerService */
    private $quoteManagerService;
    /** @var  EntityManager | PHPUnit_Framework_MockObject_MockObject */
    private $emMock;
    /** @var NullLogger | PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;
    /** @var TradeRepository | PHPUnit_Framework_MockObject_MockObject */
    private $tradeRepoMock;
    /** @var SymbolRepository | PHPUnit_Framework_MockObject_MockObject */
    private $symbolRepoMock;
    /** @var ApcuAdapter | PHPUnit_Framework_MockObject_MockObject  */
    private $cacheMock;

    public function setUp()
    {
        $this->emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->tradeRepoMock = $this->getMockBuilder(TradeRepository::class)->disableOriginalConstructor()->getMock();
        $this->symbolRepoMock = $this->getMockBuilder(SymbolRepository::class)->disableOriginalConstructor()->getMock();
        $this->loggerMock = $this->getMockBuilder(NullLogger::class)->disableOriginalConstructor()->getMock();
        $this->cacheMock = $this->getMockBuilder(ApcuAdapter::class)->disableOriginalConstructor()->getMock();
        $this->cacheMock->expects($this->any())->method('getItem')->willReturn(new CacheItem());
        $this->quoteManagerService = new QuoteManagerService(
            $this->tradeRepoMock,
            $this->symbolRepoMock,
            $this->cacheMock

        );

        $this->quoteManagerService->setEntityManager($this->emMock);
        $this->quoteManagerService->setLogger($this->loggerMock);
    }

    public function testMessageToQuote()
    {
        $message = json_encode([
            'exchange' => 'poloniex',
            'symbol' => 'BTCBTS',
            'volume' => '43.50101005',
            'price' => '0.00000396',
            'time' => '2017-02-03T15:35:52.022Z',
        ]);
        $result = $this->quoteManagerService->getTradeFromMessage($message);
        $this->assertInstanceOf('DataModelBundle\Entity\Trade', $result);
        $this->assertEquals('poloniex', $result->getExchangeName());
        $this->assertInstanceOf('DataModelBundle\Entity\Symbol', $result->getSymbol());
        $this->assertEquals(43.50101005, $result->getVolume());
        $this->assertEquals(0.00000396, $result->getPrice());
        $this->assertEquals(new \DateTime('2017-02-03T15:35:52.022Z'), $result->getTime());
    }

    public function testMessageForKnownSymbol()
    {
        $message = json_encode([
            'exchange' => 'poloniex',
            'symbol' => 'BTCBTS',
            'volume' => '43.50101005',
            'price' => '0.00000396',
            'time' => '2017-02-03T15:35:52.022Z',
        ]);
        $symbol = new Symbol();
        $symbol->setName('test');
        $this->symbolRepoMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn($symbol);
        $result = $this->quoteManagerService->getTradeFromMessage($message);
        $this->assertInstanceOf('DataModelBundle\Entity\Trade', $result);
        $this->assertEquals('test', $result->getSymbol()->getName());
    }

    public function testExceptionOnMalformedMessage()
    {
        $this->expectException('InvalidArgumentException');
        $this->quoteManagerService->getTradeFromMessage('malformed');
    }

    public function testExceptionOnMalformedMessageFieldMissing()
    {
        $this->expectException('InvalidArgumentException');
        $this->quoteManagerService->getTradeFromMessage(
            json_encode(
                [
                    'exchange' => 'poloniex',
                    'volume' => '43.50101005',
                    'price' => '0.00000396',
                    'time' => '2017-02-03T15:35:52.022Z',
                ]
            )
        );
    }

    /**
     * @param string  $message
     * @param boolean $valid
     * @dataProvider sanitizeMessageDataProvider
     */
    public function testMessageToQuoteSanitizeMessage($message, $valid)
    {
        $result = $this->quoteManagerService->sanitizeMessage($message);
        $this->assertEquals($valid, $result);
    }

    public function sanitizeMessageDataProvider()
    {
        return [
            [
                json_encode(
                    [
                        'exchange' => 'poloniex',
                        'symbol' => 'BTCBTS',
                        'volume' => '43.50101005',
                        'price' => '0.00000396',
                        'time' => '2017-02-03T15:35:52.022Z',
                    ]
                ),
                true,
            ],
            [
                json_encode(
                    [
                        'exchange' => null,
                        'symbol' => null,
                        'volume' => null,
                        'price' => null,
                        'time' => null,
                    ]
                ),
                false,
            ],
            [
                json_encode([]),
                false,
            ],
        ];
    }

    public function testPersistNewTrade()
    {
        $this->emMock->expects($this->once())
            ->method('persist');
        $result = $this->quoteManagerService->saveTradeFromMessage(
            json_encode(
                [
                    'exchange' => 'poloniex',
                    'symbol' => 'BTCBTS',
                    'volume' => '43.50101005',
                    'price' => '0.00000396',
                    'time' => '2017-02-03T15:35:52.022Z',
                ]
            )
        );

        $this->assertInstanceOf('DataModelBundle\Entity\Trade', $result);
    }

    public function testPersistTradeDontStoreDups()
    {
        $this->tradeRepoMock->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new Trade());
        $this->emMock->expects($this->never())->method('persist');
        $result = $this->quoteManagerService->saveTradeFromMessage(
            json_encode(
                [
                    'exchange' => 'poloniex',
                    'symbol' => 'BTCBTS',
                    'volume' => '43.50101005',
                    'price' => '0.00000396',
                    'time' => '2017-02-03T15:35:52.022Z',
                ]
            )
        );

        $this->assertNull($result);
    }
}
