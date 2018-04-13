<?php

namespace Tests\Functional;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DropRaisePredictionTest extends WebTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var  Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testShowDropRaisePrediction()
    {
        $crawler = $this->client->request('GET', '/prediction/drop-raise');
        $this->assertEquals(
            1,
            $crawler->filter('html:contains("Raise-Drop-Network")')->count()
        );
    }
}