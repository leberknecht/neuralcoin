<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;

class EditNetworkTest extends WebTestCase
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

    public function testEditNetwork()
    {
        /** @var Network $network */
        $network = $this->networkRepo->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $this->assertEquals('4 hours', $network->getTimeScope());
        $crawler = $this->client->request('GET', '/network/' . $network->getId() . '/edit');

        $form = $crawler->selectButton('Submit')->form();
        $form['edit_network_form[timeScope]'] = '6 hours';

        $crawler = $this->client->submit($form);
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Network updated!")')->count()
        );

        $this->em->refresh($network);
        $this->assertEquals('6 hours', $network->getTimeScope());
    }
}
