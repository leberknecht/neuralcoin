<?php

namespace Tests\DataModelBundle\Service;

use DataModelBundle\Service\SerializerService;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Serializer\Serializer;
use Tests\DataModelBundle\Fixture\SerializerMock;

class SerializerServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SerializerService */
    private $serializerService;
    /** @var  Serializer | PHPUnit_Framework_MockObject_MockObject */
    private $serializerMock;

    public function setUp()
    {
        $this->serializerMock = $this->getMockBuilder(SerializerMock::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializerService = new SerializerService(
            $this->serializerMock
        );
    }

    public function testSerialize()
    {
        $stdClass = new \stdClass();
        $stdClass->test = 42;
        $this->serializerMock->expects($this->once())
            ->method('serialize')
            ->with($stdClass, 'json', []);
        $this->serializerService->serialize($stdClass);
    }

    public function testSerializerInit()
    {
        $serializerService = new SerializerService();
        $testClass = new SerializerMock();
        $now = new \DateTime();
        $testClass->setTime($now);
        $actual = $serializerService->serialize($testClass);
        $this->assertEquals('{"time":"'.$now->format(\DateTime::ISO8601).'","publicField":42}', $actual);
    }
}
