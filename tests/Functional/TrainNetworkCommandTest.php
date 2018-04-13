<?php

namespace Tests\Functional;

use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\DataFixtures\ORM\LoadTrainingRunData;
use DataModelBundle\Entity\Network;
use Doctrine\ORM\EntityManager;
use NetworkBundle\Command\TrainNetworkCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TrainNetworkCommandTest extends KernelTestCase
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
        /** @var Network $raiseDropNetwork */
        $raiseDropNetwork = $container->get('nc.repo.network')->findOneBy([
            'name' => LoadNetworkData::DROP_RAISE_NETWORK_NAME
        ]);
        $raiseDropNetwork->setAutopilot(false);
        $this->em->remove($container->get('nc.repo.training_run')->findOneBy(['id' => LoadTrainingRunData::REF_TRAINING_RUN_ID]));
        $this->em->flush();
        $application = new Application(self::$kernel);
        $application->add(new TrainNetworkCommand());

        $command = $application->find('neuralcoin:train-network');
        $this->commandTester = new CommandTester($command);
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testTrainNetworkCommandHappyFlow()
    {
        /** @var Network $network */
        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $numberOfTrainings = $network->getTrainingRuns()->count();
        $this->commandTester->execute([
            'command' => 'neuralcoin:train-network',
            'networkId' => $network->getId()
        ]);

        $this->em->refresh($network);
        $output = $this->commandTester->getDisplay();
        $expectations = [
            'train network: ' . $network->getId(),
            sprintf('Scheduled training for network: %s training run id: %s',
                $network->getName(), $network->getTrainingRuns()->last()->getId()
            )
        ];

        foreach($expectations as $expectation) {
            $this->assertContains($expectation, $output);
        }
        $this->assertEquals($numberOfTrainings + 1, $network->getTrainingRuns()->count());
    }

    public function testTrainNetworkUnknownNetwork()
    {
        $this->expectException(\Exception::class);

        $this->em->flush();
        $this->commandTester->execute([
            'command' => 'neuralcoin:train-network',
            'networkId' => 'i dont exist'
        ]);

        $output = $this->commandTester->getDisplay();
        $expectations = [
            'unknown network: i dont exist'
        ];

        foreach($expectations as $expectation) {
            $this->assertContains($expectation, $output);
        }
    }

    public function testFetchAllAutopilotNetworks()
    {
        /** @var Network $network */
        $this->commandTester->execute([
            'command' => 'neuralcoin:train-network',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertEquals('', $output);

        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);
        $network->setAutopilot(true);
        $this->em->flush($network);
        $this->commandTester->execute([
            'command' => 'neuralcoin:train-network',
        ]);

        $output = $this->commandTester->getDisplay();
        $expectations = [
            'train network: ' . $network->getId(),
            sprintf('Scheduled training for network: %s training run id: %s',
                $network->getName(), $network->getTrainingRuns()->last()->getId()
            )
        ];

        foreach($expectations as $expectation) {
            $this->assertContains($expectation, $output);
        }
    }
}
