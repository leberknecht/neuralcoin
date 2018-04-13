<?php

namespace DataModelBundle\EventListener;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Repository\TradeRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;

class PostPersistListener
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();
        if ($entity instanceof Trade) {
            if (rand(1,10) % 5 == 0) {
                /** @var Trade $entity */
                $symbol = $entity->getSymbol();
                $symbol->setTradesCount(
                    $entityManager->getRepository('DataModelBundle:Trade')->createQueryBuilder('t')
                        ->select('count(t.id)')
                        ->where('t.symbol = :symbol')
                        ->setParameter('symbol', $symbol)
                        ->getQuery()->getSingleScalarResult()
                );
                $entityManager->flush($symbol);
            }

            if (rand(1,20) % 5 == 0) {
                /** @var Trade $entity */
                $symbol = $entity->getSymbol();

                /** @var TradeRepository $repo */
                $repo = $entityManager->getRepository(Trade::class);
                $symbol->setAverageStepsPerMinute($repo->findAvgTradesPerMinuteForLastHour($symbol));

                $entityManager->flush($symbol);
            }
        }
    }
}