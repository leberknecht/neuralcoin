<?php

namespace Tests\Functional;


use DataModelBundle\DataFixtures\ORM\LoadNetworkData;
use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Prediction;
use Doctrine\ORM\EntityManager;
use NetworkBundle\Command\CheckPredictionsCommand;
use NetworkBundle\Command\CreatePredictionCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckPredictionsCommandTest extends KernelTestCase
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
        $application->add(new CheckPredictionsCommand());

        $command = $application->find('neuralcoin:predictions:check');
        $this->commandTester = new CommandTester($command);

    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testNoPredictionsPending()
    {
        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => LoadNetworkData::REF_TEST_NETWORK]);

        $this->assertEquals(1, $network->getPredictions()->count());
        /** @var Prediction $prediction */
        $prediction = $network->getPredictions()->first();
        $this->assertFalse($prediction->isFinished());

        $this->commandTester->execute([
            'command' => 'neuralcoin:predictions:check'
        ]);

        $expected = 'prediction '.$prediction->getId().' finished. Price at prediction: 1, predicted value: 0.8, actual-price: 1.8, change -20 / 80 (predicted, actual)';
        $output = $this->commandTester->getDisplay();
        $this->assertContains($expected, $output);

    }
}
