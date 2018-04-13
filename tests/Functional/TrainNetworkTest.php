<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TrainNetworkTest extends WebTestCase
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

    public function testTrainNetwork()
    {
        /** @var Network $network */
        $network = $this->networkRepo->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $this->client->request('GET', '/network/' . $network->getId() . '/train');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $crawler = $this->client->followRedirect();

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Training scheduled, run-id:")')->count()
        );
    }
}
