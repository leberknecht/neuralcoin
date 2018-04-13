<?php

namespace DataModelBundle\Service;

use DataModelBundle\Entity\Symbol;

class DateHelperService
{
    /**
     * @param Symbol[] | array $symbols
     * @return \DateTime
     */
    public function getMaxDate(array $symbols)
    {
        return $this->getMinOrMaxDate($symbols, 'max');
    }

    /**
     * @param Symbol[] | array $symbols
     * @return \DateTime
     */
    public function getMinDate($symbols)
    {
        return $this->getMinOrMaxDate($symbols, 'min');
    }

    /**
     * @param Symbol[]|array $symbols
     * @param string $minOrMax
     * @return \DateTime
     */
    private function getMinOrMaxDate(array $symbols, $minOrMax = 'min')
    {
        $minDate = null;
        $maxDate = null;
        foreach ($symbols as $symbol) {
            foreach ($symbol->getTrades() as $trade) {
                if (!$maxDate || $trade->getTime() > $maxDate) {
                    $maxDate = $trade->getTime();
                }
                if (!$minDate || $trade->getTime() < $minDate) {
                    $minDate = $trade->getTime();
                }
            }
        }
        if ($minOrMax == 'min') {
            return $minDate;
        }
        return $maxDate;
    }
}