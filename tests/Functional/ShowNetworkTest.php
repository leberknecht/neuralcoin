<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;

class ShowNetworkTest extends WebTestCase
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

    public function testShowNetwork()
    {
        /** @var Network $network */
        $network = $this->networkRepo->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $this->assertEquals('4 hours', $network->getTimeScope());
        $crawler = $this->client->request('GET', '/network/' . $network->getId() . '/show');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("' . LoadNetworkData::REF_TEST_NETWORK . '")')->count()
        );

        $this->em->refresh($network);
        $this->assertEquals('4 hours', $network->getTimeScope());
    }
}
