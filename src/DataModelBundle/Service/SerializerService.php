<?php

namespace DataModelBundle\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerService
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(SerializerInterface $serializer = null)
    {
        if ($serializer) {
            $this->serializer = $serializer;
        } else {
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $objectNormalizer = new ObjectNormalizer($classMetadataFactory);

            $callback = function ($dateTime) {
                return $dateTime instanceof \DateTime
                    ? $dateTime->format(\DateTime::ISO8601)
                    : '';
            };

            $objectNormalizer->setCallbacks(array('createdAt' => $callback));
            $objectNormalizer->setCallbacks(array('time' => $callback));

            $objectNormalizer->setCircularReferenceHandler(function ($object) {
                /** @var \stdClass $object */
                return $object->__toString();
            });
            $this->serializer = new Serializer([$objectNormalizer], [new JsonEncoder()]);
        }
    }

    public function serialize($data, array $groups = null)
    {
        $options = [];
        if ($groups) {
            $options['groups'] = $groups;
        }
        return $this->serializer->serialize($data, 'json', $options);
    }

    /**
     * @param string $data
     * @param string $class
     * @return object
     */
    public function deserialize($data, $class)
    {
        return $this->serializer->deserialize($data, $class, 'json');
    }
}
