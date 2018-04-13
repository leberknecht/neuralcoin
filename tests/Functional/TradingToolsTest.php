<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadSymbolData;
use DataModelBundle\DataFixtures\ORM\LoadTradeData;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Repository\TradeRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

class TradingToolsTest extends WebTestCase
{
    /** @var EntityManager */
    private $em;
    /** @var TradeRepository */
    private $tradeRepo;
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->tradeRepo = self::$kernel->getContainer()->get('nc.repo.trade');
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testGetKnownSymbold()
    {
        $this->client->request('GET', '/trading-tools/known-symbols');
        /** @var Response $response */
        $response = $this->client->getResponse();
        $this->assertEquals('["' . LoadSymbolData::REF_TEST_SYMBOL . '"]', $response->getContent());
    }

    public function testGetSymbolData()
    {
        $this->client->request('GET',
            '/trading-tools/symbol-data/' .
            LoadSymbolData::REF_TEST_SYMBOL. '/' .
            LoadTradeData::REF_TEST_EXCHANGE_NAME .
            '/500'
        );
        /** @var Response $response */
        $response = $this->client->getResponse();
        $this->assertTrue(strpos($response->getContent(), '"price":1.5') !== false);
        $this->assertTrue(strpos($response->getContent(), '"price":1.8') !== false);
    }

    public function testTradingToolsViewAction()
    {
        $this->client->request('GET','/');
        $crawler = $this->client->getCrawler();
        $elements = $crawler->filter('div.trading-tools-container');
        $this->assertCount(1, $elements);
    }

    public function testGetSupportedSymbols()
    {
        $this->client->request('GET','/symbols/exchanges/' . LoadTradeData::REF_TEST_EXCHANGE_NAME);
        $jsonResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(LoadSymbolData::REF_TEST_SYMBOL, $jsonResponse[0]['name']);

        $this->client->request('GET','/symbols/exchanges/' . LoadTradeData::REF_TEST_EXCHANGE_NAME . '?useOrderBooks=1');
        $jsonResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(LoadSymbolData::REF_TEST_SYMBOL, $jsonResponse[0]['name']);
    }

    public function testGetHighRaises()
    {
        /** @var Trade[] $trades */
        $trades = $this->tradeRepo->findBy(
            [ 'exchangeName' => LoadTradeData::REF_TEST_EXCHANGE_NAME ],
            ['time' => 'DESC']
        );
        $timeDiff = (new \DateTime())->getTimestamp() - $trades[count($trades) - 1]->getTime()->getTimestamp();
        $timeDiff = (int)$timeDiff - 1;
        $url = '/trading-tools/high-raises?raiseLimit=0.1&timeScope=-';
        $url .=  ($timeDiff - 1) . ' seconds&exchangeName=' . LoadTradeData::REF_TEST_EXCHANGE_NAME;
        $this->client->request('GET', $url);
        /** @var Response $response */
        $response = $this->client->getResponse();
        $response = json_decode($response->getContent(), true);
        $this->assertNotEmpty($response[0]['old']);
        $this->assertNotEmpty($response[0]['current']);
    }
}
