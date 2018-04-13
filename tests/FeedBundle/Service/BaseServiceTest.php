<?php

namespace Tests\FeedBundle\Service;

use Psr\Log\NullLogger;
use Tests\FeedBundle\Fixture\ExtendedBaseServiceFixture;

class BaseServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testLoggerServiceNamePrefix()
    {
        $extendedBaseServiceFixture = new ExtendedBaseServiceFixture();
        $loggerMock = $this->getMockBuilder(NullLogger::class)->disableOriginalConstructor()->getMock();
        $loggerMock->expects($this->once())->method('info')->with('[extended-base-service-fixture] test');
        $loggerMock->expects($this->once())->method('error')->with('[extended-base-service-fixture] test');
        $loggerMock->expects($this->once())->method('warning')->with('[extended-base-service-fixture] test');
        $loggerMock->expects($this->once())->method('debug')->with('[extended-base-service-fixture] test');
        $extendedBaseServiceFixture->setLogger($loggerMock);
        $extendedBaseServiceFixture->logInfo('test');
        $extendedBaseServiceFixture->logError('test');
        $extendedBaseServiceFixture->logDebug('test');
        $extendedBaseServiceFixture->logWarning('test');
    }
}
