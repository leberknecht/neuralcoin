<?php


namespace Tests\Functional;


use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GetQueueStatusTest extends WebTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testGetQueueStatus()
    {
        $this->client->request('GET', '/queue/status');
        $responseContent = $this->client->getResponse()->getContent();
        $decodedContent = json_decode($responseContent, true);
        $this->assertCount(5, $decodedContent);
    }
}