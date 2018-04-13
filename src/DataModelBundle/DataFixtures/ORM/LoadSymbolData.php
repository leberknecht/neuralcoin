<?php

namespace DataModelBundle\DataFixtures\ORM;

use DataModelBundle\Entity\Symbol;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadSymbolData extends AbstractFixture
{
    const REF_TEST_SYMBOL = 'test-symbol';

    public function load(ObjectManager $manager)
    {
        $symbol = new Symbol();
        $symbol->setName(self::REF_TEST_SYMBOL);
        $symbol->setExchanges([LoadTradeData::REF_TEST_EXCHANGE_NAME]);
        $manager->persist($symbol);
        $manager->flush();
        $this->addReference(self::REF_TEST_SYMBOL, $symbol);
    }
}
