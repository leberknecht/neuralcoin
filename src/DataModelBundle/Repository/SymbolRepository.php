<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class SymbolRepository extends EntityRepository
{
    const PREVIEW_TIME_SCOPE = '-4 hours';
    /**
     * @param Network $network
     * @return array|Symbol[]
     * see @Trade
     */
    public function getSymbolsPreview(Network $network)
    {
        $maxPreviewTime = new \DateTime(self::PREVIEW_TIME_SCOPE);
        $targetDate = new \DateTime('-' . $network->getTimeScope());
        if ($targetDate->getTimestamp() < $maxPreviewTime->getTimestamp()) {
            $targetDate = $maxPreviewTime;
        }

        if ($network->isInterpolateInputs()) {
            return $this->getInterpolatedSymbols($network, $targetDate);
        }

        return $this->getSymbolsForTraining($network, $targetDate);
    }

    /**
     * @param Network $network
     * @param \DateTime|null $targetDate
     * @param Symbol|null $symbolOverwrite
     * @return array|Symbol[]|ArrayCollection
     */
    public function getSymbolsForTraining(Network $network, \DateTime $targetDate = null, Symbol $symbolOverwrite = null)
    {
        if (empty($targetDate)) {
            $targetDate = new \DateTime('-'.$network->getTimeScope());
        }

        if ($network->isInterpolateInputs()) {
            return $this->getInterpolatedSymbols($network, $targetDate, $symbolOverwrite);
        }

        /** @var Symbol[] $symbols */
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->addSelect('t')
            ->join('s.trades', 't')
            ->where('t.symbol in (:symbol)')
            ->andWhere('t.time >= :timeOffset');

        if($exchange = $network->getExchange()) {
            $qb->andWhere("t.exchangeName LIKE :exchange")
                ->setParameter('exchange', '%' . $exchange . '%');
        }
        $symbols = $qb->setParameter('symbol', $symbolOverwrite ? new ArrayCollection([$symbolOverwrite]) : $network->getSymbols())
            ->setParameter('timeOffset', $targetDate)
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('t.time', 'ASC')
            ->getQuery()
            ->getResult();

        $this->truncToSameLength($symbols);

        return $symbols;
    }

    /**
     * @param Symbol[] $symbols
     */
    protected function truncToSameLength($symbols)
    {
        $min = null;
        foreach ($symbols as $symbol) {
            if (null == $min || $min > $symbol->getTrades()->count()) {
                $min = $symbol->getTrades()->count();
            }
        }
        foreach ($symbols as $symbol) {
            $symbol->setTrades(new ArrayCollection($symbol->getTrades()->slice(0, $min)));
        }
    }

    /**
     * @return array | Symbol[]
     */
    public function findKnownSymbols()
    {
        return $this->createQueryBuilder('s')
            ->distinct(true)
            ->orderBy('s.tradesCount')
            ->getQuery()->getResult();
    }

    /**
     * @param Network $network
     * @param \DateTime $targetDate
     * @param Symbol|null $symbolOverwrite
     * @return array
     */
    public function getInterpolatedSymbols(Network $network, \DateTime $targetDate, Symbol $symbolOverwrite = null):array
    {
        /** @var ArrayCollection|Trade[] $symbolTrades */
        $symbolTrades = [];
        if (!empty($symbolOverwrite)) {
            $symbols = new ArrayCollection([$symbolOverwrite]);
        } else {
            $symbols = $network->getSymbols();
        }

        foreach ($symbols as $symbol) {
            $symbolTrades[$symbol->getId()] = new ArrayCollection();
        }

        foreach($symbols as $symbol) {
            $trades = $this->getInterpolatedSymbolTrades($network, $symbol, $targetDate);
            $prices = array_column($trades, 'price');
            $volumes = array_column($trades, 'volume');
            $minPrice = min(array_diff(array_map('floatval', $prices), [0]));
            $minVolume = min(array_diff(array_map('floatval', $volumes), [0]));
            foreach ($trades as $tradeData) {
                $trade = $this->createTradeFromArray($tradeData, $minPrice, $minVolume, $symbol);
                $symbolTrades[$symbol->getId()][] = $trade;
            }
        }

        foreach($symbols as $symbol) {
            $symbol->setTrades($symbolTrades[$symbol->getId()]);
        }

        return $symbols->toArray();
    }

    /**
     * @param $tradeData
     * @param $minPrice
     * @param $minVolume
     * @param $symbol
     * @return Trade
     */
    private function createTradeFromArray($tradeData, $minPrice, $minVolume, $symbol): Trade
    {
        $trade = new Trade();
        $trade->setId($tradeData['time']);
        $trade->setPrice(empty($tradeData['price']) ? $minPrice : $tradeData['price']);
        $trade->setVolume(empty($tradeData['volume']) ? $minVolume : $tradeData['volume']);
        $trade->setSymbol($symbol);
        $trade->setCreatedAt(new \DateTime($tradeData['time']));
        $trade->setTime(new \DateTime($tradeData['time']));
        return $trade;
    }

    /**
     * @param Network $network
     * @param Symbol $symbol
     * @param \DateTime $targetDate
     * @return array|Trade[]
     */
    private function getInterpolatedSymbolTrades(Network $network, Symbol $symbol, \DateTime $targetDate)
    {
        $now = new \DateTime();
        $timeDiffSeconds = (int)($now->getTimestamp()) - $targetDate->getTimestamp();
        $limit = (int)($timeDiffSeconds / $network->getInterpolationInterval());
        $symbolId = (int)$symbol->getId();
        $interval = (int)$network->getInterpolationInterval();
        $symbol->setTrades(new ArrayCollection());

        if (empty($network->getExchange())) {
            $exchanges = $this->getEntityManager()->getRepository('DataModelBundle:Trade')->getKnownExchanges();
            $exchanges = "'" . str_replace(',', "','", implode(',', $exchanges)) . "'";
        } else {
            $exchanges = "'" . $network->getExchange() . "'";
        }

        $stmt = $this->_em->getConnection()->prepare(
            '
            select
              unix_timestamp(times.time_offset) as id,
              times.time_offset as time,              
              @prev := coalesce(trades.price, @prev) as price,
              @prevVol := coalesce(trades.volume, @prevVol) as volume
            FROM (SELECT @prev := null, @prevVol := null) init, (
              #
              # create a sequence of dates 
              #
              SELECT
                DATE_SUB(
                    from_unixtime(unix_timestamp(NOW()) - mod(unix_timestamp(NOW()), :interpolationInterval)),
                    INTERVAL :interpolationInterval * `i` second
                ) as time_offset
              FROM (
                     SELECT @row := @row + 1 as i
                     FROM trade t, (SELECT @row := 0) r
                     limit :limit
                   ) as tmp
              ORDER BY i ASC
            ) as times
            left join (
                select
                    avg(price) as price,
                    avg(volume) as volume,
                    from_unixtime(unix_timestamp(trade.time) - mod(unix_timestamp(trade.time), :interpolationInterval)) as time_start
                from trade where symbol_id = :symbolId and
                    trade.time > from_unixtime(unix_timestamp(NOW()) - mod(unix_timestamp(now()), :interpolationInterval) - :timescopeSeconds) and                                  trade.time < from_unixtime(unix_timestamp(NOW()) - mod(unix_timestamp(now()), :interpolationInterval)) 
                    '. (empty($network->getExchange()) ? '' : 'and exchange_name in (' . $exchanges . ')') . '
                group by UNIX_TIMESTAMP(trade.time) div :interpolationInterval
                limit :limit
                ) as trades
            on times.time_offset = trades.time_start order by time ASC
        '
        );

        $stmt->bindParam(':interpolationInterval', $interval, \PDO::PARAM_INT);
        $stmt->bindParam(':timescopeSeconds', $timeDiffSeconds, \PDO::PARAM_INT);
        $stmt->bindParam(':symbolId', $symbolId, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * @param string $exchange
     * @param bool $useOrderBooks
     * @return array|Symbol[]|ArrayCollection
     */
    public function findSupportedSymbols(string $exchange, bool $useOrderBooks)
    {
        $qb = $this->createQueryBuilder('s')->distinct(true);
        if (!empty($exchange)) {
            $qb->where("s.exchanges like :exchange")
                ->setParameter('exchange', '%'. $exchange . '%');
        }

        if ($useOrderBooks) {
            $qb->join('s.orderBooks', 'o');
        }

        return $qb->orderBy('s.averageStepsPerMinute', 'DESC')
            ->getQuery()->getResult();
    }

    /**
     * @param string $exchange
     * @return array|Symbol[]
     */
    public function findSymbolsForExchange($exchange)
    {
        return $this->createQueryBuilder('s')
            ->distinct(true)
            ->join('s.trades', 't')
            ->where('t.exchangeName = :exchange')
            ->setParameter('exchange', $exchange)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @param $count
     * @param $exchange
     * @return ArrayCollection|Symbol[]|array
     */
    public function findMostActive($count, $exchange)
    {
        $qb = $this->createQueryBuilder('s');
        if (!empty($exchange)) {
            $qb->andWhere('s.exchanges LIKE :exchange')
                ->setParameter('exchange', '%'.$exchange.'%');
        }
        $qb->orderBy('s.averageStepsPerMinute', 'DESC');
        $qb->setMaxResults($count);
        return $qb->getQuery()->getResult();
    }
}
