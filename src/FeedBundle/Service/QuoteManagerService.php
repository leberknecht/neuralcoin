<?php

namespace FeedBundle\Service;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Repository\TradeRepository;
use DataModelBundle\Service\BaseService;
use DateTime;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\CacheItem;

class QuoteManagerService extends BaseService
{
    /**
     * @var TradeRepository
     */
    private $tradeRepository;
    /**
     * @var SymbolRepository
     */
    private $symbolRepository;
    /**
     * @var AbstractAdapter
     */
    private $cache;

    public function __construct(
        TradeRepository $tradeRepository,
        SymbolRepository $symbolRepository,
        AbstractAdapter $cache
    ) {
        $this->tradeRepository = $tradeRepository;
        $this->symbolRepository = $symbolRepository;
        $this->cache = $cache;
    }

    /**
     * @param $message
     * @return bool
     */
    private function checkCache($message)
    {
        $obj = json_decode($message, true);
        $key = $obj['symbol'].'price';
        /** @var CacheItem $cacheItem */
        $cacheItem = $this->cache->getItem('trade_messages'.$key);
        if ($cacheItem && $cacheItem->isHit() && $cacheItem->get() == $obj['price']) {
            return true;
        }

        $cacheItem->set($obj['price']);
        $cacheItem->expiresAt(new \DateTime('+1 minute'));
        $this->cache->save($cacheItem);

        return false;
    }

    /**
     * @param string $message
     * @return Trade | null
     */
    public function saveTradeFromMessage(string $message)
    {
        if (true == $this->checkCache($message)) {
            return null;
        }

        $trade = $this->getTradeFromMessage($message);
        if (!empty($trade->getSymbol()->getId())) {
            $lastPrice = $this->tradeRepository->findLastPrice($trade->getSymbol(), false, $trade->getExchangeName());
        } else {
            $lastPrice = 0.0;
        }

        $existingTrade = $this->tradeRepository->findOneBy([
            'exchangeName' => $trade->getExchangeName(),
            'time' => $trade->getTime(),
            'symbol' => $trade->getSymbol(),
        ]);
        $lastPrice = round($lastPrice, 8);
        $currentPrice = round($trade->getPrice(), 8);

        if (!$existingTrade && $lastPrice != $currentPrice) {
            $this->em->persist($trade);
            $this->em->flush($trade);
            return $trade;
        }

        return null;
    }

    /**
     * @param string $message
     * @return Trade
     */
    public function getTradeFromMessage(string $message): Trade
    {
        if (!$this->sanitizeMessage($message)) {
            throw new \InvalidArgumentException('malformed message, required fields missing');
        }

        $payload = json_decode($message, true);
        $quote = new Trade();
        $quote->setExchangeName($payload['exchange']);
        $quote->setSymbol($this->getSymbolFromName($payload));
        $quote->setPrice($payload['price']);
        $quote->setVolume($payload['volume']);
        $quote->setTime(new DateTime($payload['time']));

        return $quote;
    }

    /**
     * @param string $message
     * @return bool
     */
    public function sanitizeMessage(string $message): bool
    {
        $payload = json_decode($message, true);
        $requiredFields = [
            'exchange',
            'symbol',
            'price',
            'volume',
            'time',
        ];

        foreach ($requiredFields as $requiredField) {
            if (empty($payload[$requiredField])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $payload
     * @return Symbol
     */
    private function getSymbolFromName(array $payload): Symbol
    {
        $symbol = $this->symbolRepository->findOneBy(['name' => $payload['symbol']]);
        if (empty($symbol)) {
            $symbol = new Symbol();
            $symbol->setName($payload['symbol']);
        }

        return $symbol;
    }
}
