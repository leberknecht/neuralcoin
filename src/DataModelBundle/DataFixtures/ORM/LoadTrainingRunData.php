<?php

namespace DataModelBundle\DataFixtures\ORM;


use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\TrainingRun;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTrainingRunData extends AbstractFixture implements DependentFixtureInterface
{
    const REF_TRAINING_RUN_ID = 'test-training-run';

    public function load(ObjectManager $manager)
    {
        /** @var Network $network */
        $network = $this->getReference(LoadNetworkData::REF_TEST_NETWORK);
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork($network);
        $trainingRun->setError(0.25);

        $trainingRun->setFinishedAt(new \DateTime('-3 minutes'));
        $trainingRun->setStartedAt(new \DateTime('-5 minutes'));
        $trainingRun->setTrainingDataPreparedAt(new \DateTime('-4 minutes'));
        $trainingRun->setRawOutput('test');
        $trainingRun->setStatus(TrainingRun::STATUS_FINISHED);
        $trainingRun->setNetwork($network);
        $trainingRun->setImagePath('');
        $manager->persist($trainingRun);
        $manager->flush();
        $trainingRun->setId(self::REF_TRAINING_RUN_ID);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [LoadNetworkData::class];
    }
}