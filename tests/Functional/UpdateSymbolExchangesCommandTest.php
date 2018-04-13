<?php


namespace Tests\Functional;

use DataModelBundle\Command\UpdateSymbolExchangesCommand;
use DataModelBundle\DataFixtures\ORM\LoadSymbolData;
use DataModelBundle\Entity\Symbol;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class UpdateSymbolExchangesCommandTest  extends KernelTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var CommandTester  */
    private $commandTester;
    /** @var Symbol */
    private $symbol;

    public function setUp()
    {
        self::bootKernel();
        $container = self::$kernel->getContainer();
        $this->em = $container->get('doctrine.orm.default_entity_manager');

        $this->em->beginTransaction();
        $this->symbol = $container->get('nc.repo.symbol')->findOneBy([
            'name' => LoadSymbolData::REF_TEST_SYMBOL
        ]);
        $this->symbol->setExchanges([]);
        $this->em->flush();
        $application = new Application(self::$kernel);
        $application->add(new UpdateSymbolExchangesCommand());

        $command = $application->find('neuralcoin:update-symbol-exchanges');
        $this->commandTester = new CommandTester($command);

    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testUpdateSymbolExchanges()
    {
        $this->assertEmpty($this->symbol->getExchanges());

        $this->commandTester->execute([
            'command' => 'neuralcoin:update-symbol-exchanges'
        ]);

        $this->em->refresh($this->symbol);
        $this->assertEquals(['test-exchange'], $this->symbol->getExchanges());
    }
}