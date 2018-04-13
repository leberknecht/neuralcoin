<?php

namespace DataModelBundle\DataFixtures\ORM;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadTradeData extends AbstractFixture implements DependentFixtureInterface
{
    const REF_TEST_EXCHANGE_NAME = 'test-exchange';

    public function load(ObjectManager $manager)
    {
        $trade = new Trade();
        /** @var Symbol $symbol */
        $symbol = $this->getReference(LoadSymbolData::REF_TEST_SYMBOL);
        $trade->setSymbol($symbol);
        $trade->setExchangeName(self::REF_TEST_EXCHANGE_NAME);
        $trade->setVolume(1);
        $trade->setTime(new \DateTime('-1 minutes'));
        $trade->setPrice(1.5);
        $trade2 = clone $trade;
        $trade2->setTime(new \DateTime('-30 seconds'));
        $trade2->setPrice(1.8);
        $manager->persist($trade);
        $manager->persist($trade2);
        $manager->flush();

        $lastTime = new \DateTime('-10 minutes');
        for($x = 0; $x < 50; $x++) {
            $trade = new Trade();
            $trade->setSymbol($symbol);
            $trade->setExchangeName(self::REF_TEST_EXCHANGE_NAME);
            $trade->setVolume(1);
            $trade->setTime($lastTime);
            $lastTime->modify('- ' . random_int((60*5), (60*10)) . ' seconds');
            $trade->setPrice((float)(random_int(1, 9) / 10));
            $manager->persist($trade);
            $manager->flush();
        }
    }

    public function getDependencies()
    {
        return [LoadSymbolData::class];
    }
}
