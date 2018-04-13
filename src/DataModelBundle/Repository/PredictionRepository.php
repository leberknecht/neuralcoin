<?php

namespace DataModelBundle\Repository;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Prediction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use PDO;

class PredictionRepository extends EntityRepository
{
    /**
     * @param Network $network
     * @return float|int
     */
    public function findDirectionHitRatio(Network $network)
    {
        $correct = $this->createQueryBuilder('p')
            ->select('count(p)')
            ->where('p.directionHit = 1')
            ->andWhere('p.finished = 1')
            ->andWhere('p.network = :network')
            ->andWhere('p.directionHit IS NOT NULL')
            ->setParameter('network', $network)
            ->getQuery()->getSingleScalarResult();
        $wrong = $this->createQueryBuilder('p')
            ->select('count(p)')
            ->where('p.directionHit = 0')
            ->andWhere('p.finished = 1')
            ->andWhere('p.network = :network')
            ->andWhere('p.directionHit IS NOT NULL')
            ->setParameter('network', $network)
            ->getQuery()->getSingleScalarResult();

        if ($wrong + $correct == 0) {
            return 0.0;
        }
        return (float)((100 / ($wrong + $correct)) * $correct);
    }

    /**
     * @param Network $network
     * @return array
     */
    public function findPredictionStats(Network $network)
    {
        $sql = "
            select
                  FROM_UNIXTIME(UNIX_TIMESTAMP(created_at) - MOD(UNIX_TIMESTAMP(created_at), 86400)) as `date`,
                  count(1) as total_count,
                  sum(direction_hit) as correct_hits
                  ,(100 / count(1)) * sum(direction_hit) as percentage
                from prediction p
                where 
                finished = 1 
                and direction_hit is not null
                and network_id = :network
                and created_at > DATE_SUB(curdate(), INTERVAL 2 WEEK)
                group by
                  UNIX_TIMESTAMP(created_at) DIV 86400
        ";

        $statement = $this->_em->getConnection()->prepare($sql);
        $statement->execute([
            'network' => $network->getId()
        ]);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);;
        return $result;
    }

    /**
     * @return array | Prediction[] | ArrayCollection
     */
    public function findLastPredictions()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()->getResult();
    }
}