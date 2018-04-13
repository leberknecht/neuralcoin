<?php

namespace DataModelBundle\DataFixtures\ORM;


use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Prediction;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadPredictionData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Network $network */
        $network = $this->getReference(LoadNetworkData::REF_TEST_NETWORK);
        $prediction = new Prediction();
        $prediction->setNetwork($network);
        $prediction->setPriceAtPrediction(1.00);
        $prediction->setPredictedValue(0.8);
        $prediction->setPredictedChange(-20);
        $manager->persist($prediction);
        $manager->flush();

        $prediction->setCreatedAt(new \DateTime('-5 minutes'));
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadNetworkData::class];
    }
}
