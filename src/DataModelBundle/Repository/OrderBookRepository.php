<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\OrderBook;
use DataModelBundle\Entity\Symbol;
use DateTime;
use Doctrine\ORM\EntityRepository;

class OrderBookRepository extends EntityRepository
{
    /**
     * @param $symbol
     * @param $exchange
     * @param $date
     * @return OrderBook|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findClosest(Symbol $symbol, $exchange, DateTime $date)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.date >= :date')
            ->andWhere('o.symbol = :symbol')
            ->setParameter('date', $date)
            ->setParameter('symbol', $symbol)
            ->orderBy('o.date', 'ASC')
            ->setMaxResults(1);
        if (!empty($exchange)) {
            $qb->andWhere('o.exchange = :exchange')
                ->setParameter('exchange', $exchange);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Symbol $symbol
     * @param DateTime $dateOffset
     * @return array|OrderBook[]
     */
    public function findOrderBooks(Symbol $symbol, \DateTime $dateOffset)
    {
        return $this->createQueryBuilder('o')
            ->where('o.symbol = :symbol')
            ->andWhere('o.date >= :dateOffset')
            ->orderBy('o.date', 'ASC')
            ->setParameter('symbol', $symbol)
            ->setParameter('dateOffset', $dateOffset)
            ->getQuery()->getResult();
    }
}
