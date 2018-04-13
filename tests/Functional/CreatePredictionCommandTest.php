<?php


namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use Doctrine\ORM\EntityManager;
use NetworkBundle\Command\CreatePredictionCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreatePredictionCommandTest extends KernelTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var CommandTester  */
    private $commandTester;

    public function setUp()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->em = $container->get('doctrine.orm.default_entity_manager');

        $this->em->beginTransaction();

        $application = new Application(self::$kernel);
        $application->add(new CreatePredictionCommand());

        /** @var Network $raiseDropNetwork */
        $raiseDropNetwork = $container->get('nc.repo.network')->findOneBy([
            'name' => LoadNetworkData::DROP_RAISE_NETWORK_NAME
        ]);
        $raiseDropNetwork->setAutopilot(false);
        $this->em->flush();

        $command = $application->find('neuralcoin:prediction:create');
        $this->commandTester = new CommandTester($command);

    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testGetPredictionCommandIdGivenHappyFlow()
    {
        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $this->commandTester->execute([
            'command' => 'neuralcoin:prediction:create',
            'networkId' => $network->getId()
        ]);

        $this->em->refresh($network);
        $expectations = [
            'create prediction for network: ' . $network->getId(),
            'Prediction created, id: ' . $network->getPredictions()->last()->getId()
        ];

        $output = $this->commandTester->getDisplay();

        foreach($expectations as $expectation) {
            $this->assertContains($expectation, $output);
        }
    }

    public function testNoIdNoAutopilot()
    {
        $this->commandTester->execute([
            'command' => 'neuralcoin:prediction:create'
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertEquals('', $output);
    }

    public function testGetPredictionCommanAutoPilot()
    {
        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $predictionCount = $network->getPredictions()->count();
        $network->setAutopilot(true);
        $this->em->flush($network);

        $this->commandTester->execute([
            'command' => 'neuralcoin:prediction:create'
        ]);

        $this->em->refresh($network);
        $expectations = [
            'create prediction for network: ' . $network->getId(),
            'Prediction created, id: ' . $network->getPredictions()->last()->getId()
        ];

        $output = $this->commandTester->getDisplay();

        foreach($expectations as $expectation) {
            $this->assertContains($expectation, $output);
        }

        $this->assertEquals($predictionCount + 1, $network->getPredictions()->count());
    }

    public function testUnknownNetworkRequested()
    {
        $this->expectException(\Exception::class);
        $this->commandTester->execute([
            'command' => 'neuralcoin:prediction:create',
            'networkId' => 'fail'
        ]);
        $output = $this->commandTester->getDisplay();

        $this->assertContains('unknown network: fail', $output);
    }
}
