<?php

namespace DataModelBundle\DataFixtures\ORM;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Symbol;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadNetworkData extends AbstractFixture implements DependentFixtureInterface
{
    const REF_TEST_NETWORK = 'test-network';
    const DROP_RAISE_NETWORK_NAME = 'Raise-Drop-Network';

    public function load(ObjectManager $manager)
    {
        $network = $this->createNetwork();
        $manager->persist($network);

        $raiseDropNetwork = $this->createNetwork();
        $raiseDropNetwork->setName(self::DROP_RAISE_NETWORK_NAME);
        $raiseDropNetwork->setAutopilot(true);
        $raiseDropNetwork->getOutputConfig()->setType(OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY);
        $manager->persist($raiseDropNetwork);
        $manager->flush();
        $this->addReference(self::REF_TEST_NETWORK, $network);
    }

    private function createNetwork()
    {
        $network = new Network();
        /** @var Symbol $symbol */
        $symbol = $this->getReference(LoadSymbolData::REF_TEST_SYMBOL);
        $network->setSymbols(new ArrayCollection([$symbol]));
        $network->setId(self::REF_TEST_NETWORK);
        $outputConfig = new OutputConfig();
        $outputConfig->setStepsAhead(1);
        $outputConfig->setPricePredictionSymbol($symbol);
        $network->setOutputConfig($outputConfig);
        $outputConfig->setNetwork($network);

        $network->setFilePath('decoy-network.xml');
        $network->setName(self::REF_TEST_NETWORK);
        $outputConfig->setPricePredictionSymbol($symbol);

        return $network;
    }

    public function getDependencies()
    {
        return [LoadSymbolData::class];
    }
}
