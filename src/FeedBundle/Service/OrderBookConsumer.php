<?php

namespace FeedBundle\Service;

use DataModelBundle\Entity\OrderBook;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Repository\TradeRepository;
use DataModelBundle\Service\BaseService;
use DataModelBundle\Service\SerializerService;
use DateTime;
use FeedBundle\Message\OrderBookMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class OrderBookConsumer extends BaseService implements ConsumerInterface
{
    /**
     * @var SerializerService
     */
    private $serializerService;
    /**
     * @var SymbolRepository
     */
    private $symbolRepository;
    /**
     * @var TradeRepository
     */
    private $tradeRepository;

    /**
     * OrderBookConsumer constructor.
     * @param SerializerService $serializerService
     * @param SymbolRepository $symbolRepository
     * @param TradeRepository $tradeRepository
     */
    public function __construct(
        SerializerService $serializerService,
        SymbolRepository $symbolRepository,
        TradeRepository $tradeRepository
    )
    {
        $this->serializerService = $serializerService;
        $this->symbolRepository = $symbolRepository;
        $this->tradeRepository = $tradeRepository;
    }

    public function execute(AMQPMessage $msg)
    {
        /** @var OrderBookMessage $orderBookMessage */
        $orderBookMessage = $this->serializerService->deserialize($msg->getBody(), OrderBookMessage::class);
        if (empty($orderBookMessage->getData()['buy']) || empty($orderBookMessage->getData()['sell'])) {
            return;
        }
        $orderBook = new OrderBook();
        $orderBook->setDate(new DateTime($orderBookMessage->getDate()));
        $orderBook->setRawData(json_encode($orderBookMessage->getData()));
        $orderBook->setExchange($orderBookMessage->getExchange());
        /** @var Symbol $symbol */
        $symbol = $this->symbolRepository->findOneBy([
            'name' => str_replace('-','', $orderBookMessage->getSymbol())
        ]);

        $orderBook->setSymbol($symbol);
        $lastPrice = $this->tradeRepository->findLastPrice($symbol, false, $orderBook->getExchange());
        $orderBook->setBuy($this->createOrderBookDataRecord($orderBookMessage->getData()['buy'], (float)$lastPrice));
        $orderBook->setSell($this->createOrderBookDataRecord($orderBookMessage->getData()['sell'], (float)$lastPrice));

        $this->em->persist($orderBook);
        $this->em->flush();
        $this->logInfo(sprintf('order book saved for symbol %s from exchange %s',
            $orderBook->getSymbol()->getName(),
            $orderBook->getExchange()
        ));
    }

    /**
     * @param array $rawData
     * @param float $lastPrice
     * @return array
     */
    private function createOrderBookDataRecord($rawData, float $lastPrice)
    {
        $recordData = [];
        foreach($rawData as $order) {
            $recordData[] = [
                (100 / $lastPrice) * ((float)$order['Rate'] - $lastPrice),
                (float)$order['Quantity'] * $lastPrice
            ];
        }
        return $recordData;
    }
}