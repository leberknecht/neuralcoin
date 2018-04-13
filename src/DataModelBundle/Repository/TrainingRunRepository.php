<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\TrainingRun;
use Doctrine\ORM\EntityRepository;

class TrainingRunRepository extends EntityRepository
{
    /**
     * @param $network
     * @return TrainingRun[]
     */
    public function findLastTrainingRuns($network = null)
    {
        if ($network) {
            return $this->createQueryBuilder('tr')
                ->where('tr.network = :network')
                ->andWhere('tr.status = :statusFinsished')
                ->orderBy('tr.createdAt', 'DESC')
                ->setParameter('network', $network)
                ->setParameter('statusFinsished', TrainingRun::STATUS_FINISHED)
                ->setMaxResults(5)
                ->getQuery()->getResult();
        }

        return $this->createQueryBuilder('tr')
            ->orderBy('tr.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();
    }
}
