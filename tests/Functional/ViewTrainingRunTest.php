<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadTrainingRunData;
use DataModelBundle\Repository\NetworkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;

class ViewTrainingRunTest extends WebTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var NetworkRepository */
    private $trainingRunRepo;
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->trainingRunRepo = self::$kernel->getContainer()->get('nc.repo.training_run');
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testViewTrainingRun()
    {
        $trainingRun = $this->trainingRunRepo->findOneBy(['id' => LoadTrainingRunData::REF_TRAINING_RUN_ID]);
        $this->client->request('GET', '/training/' . $trainingRun->getId() . '/status');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
}