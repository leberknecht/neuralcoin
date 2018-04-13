<?php


namespace Tests\DataModelBundle\Fixture;

use Symfony\Component\Serializer\SerializerInterface;

class SerializerMock implements SerializerInterface
{
    public $publicField = 42;
    private $time;

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    public function serialize($data, $format, array $context = array())
    {
    }

    public function deserialize($data, $type, $format, array $context = array())
    {
    }
}
