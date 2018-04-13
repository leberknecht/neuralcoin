<?php

namespace DataModelBundle\DataFixtures\ORM;

use DataModelBundle\Entity\OrderBook;
use DataModelBundle\Entity\Symbol;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOrderBookData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Symbol $symbol */
        $symbol = $this->getReference(LoadSymbolData::REF_TEST_SYMBOL);
        for($x = 0; $x < 10; $x++) {
            $orderBook = new OrderBook();
            $orderBook->setBuy([0.3, 9043, 0.4, 9044, 0.51, 9050]);
            $orderBook->setSell([0.1, 8023, 0.2, 8021, 0.4, 8005]);
            $orderBook->setDate(new \DateTime('-' . $x. ' min'));
            $orderBook->setExchange(LoadTradeData::REF_TEST_EXCHANGE_NAME);
            $orderBook->setSymbol($symbol);
            $manager->persist($orderBook);
        }

        $manager->flush();
    }
    public function getDependencies()
    {
        return [LoadSymbolData::class];
    }
}