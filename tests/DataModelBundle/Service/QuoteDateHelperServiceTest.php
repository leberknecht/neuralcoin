<?php

namespace Tests\DataModelBundle\Service;

use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Service\DateHelperService;
use Doctrine\Common\Collections\ArrayCollection;

class QuoteDateHelperServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateHelperService
     */
    private $quoteDateHelperService;

    public function setUp()
    {
        $this->quoteDateHelperService = new DateHelperService();
    }

    public function testGetMaxDate()
    {
        $symbol = new Symbol();
        $symbol->setTrades(new ArrayCollection([
            $this->t('2016-01-01 10:00:00'),
            $this->t('2016-03-01 10:00:00'),
            $this->t('2016-02-01 10:00:00'),
        ]));
        $actual = $this->quoteDateHelperService->getMaxDate([$symbol]);
        $this->assertEquals('2016-03-01', $actual->format('Y-m-d'));
    }

    public function testGetMinDate()
    {
        $symbol = new Symbol();
        $symbol->setTrades(new ArrayCollection([
            $this->t('2016-01-01 10:00:00'),
            $this->t('2016-03-01 10:00:00'),
            $this->t('2016-02-01 10:00:00'),
        ]));
        $actual = $this->quoteDateHelperService->getMinDate([$symbol]);
        $this->assertEquals('2016-01-01', $actual->format('Y-m-d'));
    }

    /**
     * @param string $dateString
     * @return Trade
     */
    public function t($dateString)
    {
        return ( new Trade())->setTime(new \DateTime($dateString));
    }
}