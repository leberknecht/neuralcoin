<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class TradeRepository extends EntityRepository
{
    /**
     * @param Symbol|string $getSymbol
     * @param bool $refresh
     * @param string $exchange
     * @return float|null
     * @todo we must check for exchange here, may require frontend work
     */
    public function findLastPrice(Symbol $getSymbol, $refresh = false, $exchange = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.symbol = :symbol')
            ->orderBy('t.createdAt', 'DESC')
            ->setParameter('symbol', $getSymbol)
            ;
        if (!empty($exchange)) {
            $qb->andWhere('t.exchangeName = :exchange')
                ->setParameter('exchange', $exchange);
        }
        /** @var Trade $lastTrade */
        $lastTrade = $qb->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($refresh) {
            $this->_em->refresh($lastTrade);
        }

        if ($lastTrade) {
            return $lastTrade->getPrice();
        }
        return 0.0;
    }

    /**
     * @param Prediction $prediction
     * @return Trade | null
     */
    public function findPredictedTrade(Prediction $prediction)
    {
        $targetDate = clone $prediction->getCreatedAt();
        $outputConfig = $prediction->getNetwork()->getOutputConfig();
        $qb = $this->createQueryBuilder('t');
        $qb->where('t.symbol = :symbol')
            ->andWhere('t.time > :targetDate')
            ->setParameter('targetDate', $targetDate)
            ->setParameter('symbol', $outputConfig->getPricePredictionSymbol())
        ;
        if ($exchange = $prediction->getNetwork()->getExchange()) {
            $qb->andWhere('t.exchangeName = :exchange')
                ->setParameter('exchange', $exchange);
        }

        if ($prediction->getNetwork()->isInterpolateInputs()) {
            $timeDiff = $prediction->getNetwork()->getInterpolationInterval() * $outputConfig->getStepsAhead();
            $targetDate->modify('+' . $timeDiff . ' seconds');
            return $qb->setParameter('targetDate', $targetDate)
                ->setMaxResults(1)
                ->orderBy('t.time', 'ASC')
                ->getQuery()
                ->getOneOrNullResult();
        }
        return $this->createQueryBuilder('t')
            ->setMaxResults($outputConfig->getStepsAhead())
            ->orderBy('t.time', 'DESC')
            ->setMaxResults(1)
            ->setFirstResult($outputConfig->getStepsAhead() - 1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAvgTradesPerMinuteForLastHour(Symbol $symbol)
    {
        $queryStatement = $this->_em->getConnection()->prepare('
        
            SELECT AVG(trade_count) as avg_tades_per_minute from (
                SELECT
                count(1) as trade_count,
                ROUND(UNIX_TIMESTAMP(created_at) / (1 * 60)) AS timekey
                FROM trade
                WHERE symbol_id = ?
                AND trade.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY timekey
            ) as tmp')
        ;
        $queryStatement->execute([$symbol->getId()]);


        return $queryStatement->fetchColumn();
    }

    /**
     * @return array
     */
    public function getKnownExchanges()
    {
        return array_column($this->createQueryBuilder('t')
            ->select('t.exchangeName')
            ->distinct(true)
            ->getQuery()->getArrayResult(), 'exchangeName');
    }

    /**
     * @param Symbol $symbol
     * @param string $exchange
     * @param \DateTime $timeOffset
     * @return Trade|null
     */
    public function findTradeAgo(Symbol $symbol, string $exchange, \DateTime $timeOffset)
    {
        return $this->createQueryBuilder('t')
            ->where('t.symbol = :symbol')
            ->andWhere('t.exchangeName = :exchange')
            ->andWhere('t.time <= :dateOffset')
            ->orderBy('t.time', 'DESC')
            ->setParameter('exchange', $exchange)
            ->setParameter('dateOffset', $timeOffset)
            ->setParameter('symbol', $symbol)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Symbol $symbol
     * @param $symbol
     * @param string $exchange
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array|Trade[]
     */
    public function findTradesForTimespan(Symbol $symbol, string $exchange, \DateTime $startDate, \DateTime $endDate)
    {
        $old = $this->createQueryBuilder('t')
            ->where('t.symbol = :symbol')
            ->andWhere('t.exchangeName = :exchange')
            ->andWhere('t.time <= :dateOffset')
            ->orderBy('t.time', 'DESC')
            ->setParameter('exchange', $exchange)
            ->setParameter('dateOffset', $startDate)
            ->setParameter('symbol', $symbol)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $current = $this->createQueryBuilder('t')
            ->where('t.symbol = :symbol')
            ->andWhere('t.exchangeName = :exchange')
            ->andWhere('t.time <= :dateOffset')
            ->orderBy('t.time', 'DESC')
            ->setParameter('exchange', $exchange)
            ->setParameter('dateOffset', $endDate)
            ->setParameter('symbol', $symbol)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return [$old, $current];
    }

    /**
     * @param Symbol $symbol
     * @param $exchange
     * @param \DateTime $timeOffset
     * @return array|Trade[]|ArrayCollection
     */
    public function findSymbolTrades(Symbol $symbol, $exchange, \DateTime $timeOffset)
    {
        return $this->createQueryBuilder('t')
            ->where('t.exchangeName = :exchange')
            ->andWhere('t.symbol = :symbol')
            ->andWhere('t.time > :timeOffset')
            ->setParameter('exchange', $exchange)
            ->setParameter('symbol', $symbol)
            ->setParameter('timeOffset', $timeOffset)
            ->getQuery()->getResult();
    }
}
