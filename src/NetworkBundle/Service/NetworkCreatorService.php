<?php

namespace NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Service\BaseService;
use DataModelBundle\Service\SerializerService;
use FeedBundle\Message\CreateNetworkMessage;
use League\Flysystem\Filesystem;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;

class NetworkCreatorService extends BaseService
{
    /**
     * @var RpcClient
     */
    private $createNetworkClient;
    /**
     * @var SerializerService
     */
    private $serializerService;
    /**
     * @var Filesystem
     */
    private $networkFilesystem;

    public function __construct(
        SerializerService $serializerService,
        RpcClient $createNetworkClient,
        Filesystem $networkFilesystem
    )
    {
        $this->createNetworkClient = $createNetworkClient;
        $this->serializerService = $serializerService;
        $this->networkFilesystem = $networkFilesystem;
    }

    public function createPybrainNetwork(Network $network)
    {
        if (!$this->networkFilesystem->has($network->getId())) {
            $this->networkFilesystem->createDir($network->getId());
        }
        $this->createNetworkClient->setLogger($this->logger);
        $networkFileName = $network->getId() . DIRECTORY_SEPARATOR . $network->getId().'.tflearn';
        $network->setFilePath($networkFileName);
        $createNetworkMessage = $this->assembleMessage($network);

        $requestId = uniqid('create_network');
        $this->createNetworkClient->addRequest(
            $this->serializerService->serialize($createNetworkMessage),
            'create-network',
            $requestId
        );
        $replies = $this->createNetworkClient->getReplies();
        if (empty($replies)) {
            throw new \Exception('no reply from create-network RPC service');
        }


        $this->em->flush();
    }

    /**
     * @param Network $network
     * @return CreateNetworkMessage
     */
    private function assembleMessage(Network $network)
    {
        $createNetworkMessage = new CreateNetworkMessage();
        $createNetworkMessage->setFromNetwork($network);

        return $createNetworkMessage;
    }
}
