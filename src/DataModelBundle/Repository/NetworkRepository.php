<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

class NetworkRepository extends EntityRepository
{
    /**
     * @return array|Network[]|ArrayCollection
     */
    public function findPredictableNetworks()
    {
        return $this->createQueryBuilder('n')
            ->where('n.autopilot = 1')
            ->andWhere('n.separateInputSymbols = 0')
            ->getQuery()->getResult();
    }

    /**
     * @return array|Network[]|ArrayCollection
     */
    public function findGenericNetworks()
    {
        return $this->createQueryBuilder('n')
            ->join('n.outputConfig', 'o')
            ->where('n.separateInputSymbols = 1')
            ->andWhere('(o.type = :raiseType OR o.type = :dropType)')
            ->setParameter('raiseType', OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY)
            ->setParameter('dropType', OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY)
            ->orderBy('n.name')
            ->getQuery()->getResult();
    }
}
