<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\NetworkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResetNetworkTest extends WebTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var NetworkRepository */
    private $networkRepo;
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->networkRepo = self::$kernel->getContainer()->get('nc.repo.network');
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testResetNetwork()
    {
        /** @var Network $network */
        $network = $this->networkRepo->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork($network);
        $network->setTrainingRuns(new ArrayCollection([$trainingRun]));
        $this->em->persist($trainingRun);
        $this->em->flush();

        $this->em->refresh($network);
        $this->assertCount(2, $network->getTrainingRuns());
        $this->assertCount(1, $network->getPredictions());
        $this->client->request('GET', '/network/' . $network->getId() . '/reset');

        $this->em->refresh($network);
        $this->assertCount(0, $network->getTrainingRuns());
        $this->assertCount(0, $network->getPredictions());
    }
}