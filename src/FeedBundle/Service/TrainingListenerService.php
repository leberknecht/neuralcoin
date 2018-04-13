<?php

namespace FeedBundle\Service;

use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\TrainingRunRepository;
use DataModelBundle\Service\BaseService;
use Imagick;
use League\Flysystem\Filesystem;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class TrainingListenerService extends BaseService implements ConsumerInterface
{
    /**
     * @var TrainingRunRepository
     */
    private $trainingRunRepository;
    /**
     * @var Filesystem
     */
    private $networkFilesystem;
    /**
     * @var Filesystem
     */
    private $publicImagesFilesystem;

    public function __construct(
        TrainingRunRepository $trainingRunRepository,
        Filesystem $networkFilesystem,
        Filesystem $publicImagesFilesystem
    )
    {
        $this->trainingRunRepository = $trainingRunRepository;
        $this->networkFilesystem = $networkFilesystem;
        $this->publicImagesFilesystem = $publicImagesFilesystem;
    }

    public function execute(AMQPMessage $msg)
    {
        $message = json_decode($msg->getBody(), true);
        $this->logDebug(sprintf(
            'training update, training run: %s status: %s, raw update: %s',
            $message['trainingRunId'],
            $message['status'],
            $msg->getBody()
        ));
        /** @var TrainingRun $trainingRun */
        $trainingRun = $this->trainingRunRepository->find($message['trainingRunId']);
        $this->handleStatusUpdate($message, $trainingRun);
    }

    /**
     * @param $message
     * @param $trainingRun
     * @throws \Exception
     */
    private function handleStatusUpdate($message, TrainingRun $trainingRun)
    {
        switch ($message['status']) {
            case TrainingRun::STATUS_FINISHED:
                $trainingRun->setFinishedAt(new \DateTime());
                $trainingRun->getNetwork()->setMeanError((float)$message['error']);
                $trainingRun->setTrainingSetLength($message['trainingSetLength']);
                $trainingRun->setRawOutput($message['rawOutput']);
                $trainingRun->setError((float)$message['error']);

                $imagePath = $message['imagePath'];
                if (!empty($imagePath)) {
                    $this->processNetworkImage($trainingRun, $imagePath);
                }

                break;
            case TrainingRun::STATUS_RUNNING:
                break;
            case TrainingRun::STATUS_ERROR:
                $trainingRun->setRawOutput($message['rawOutput']);
                $this->logInfo(
                    'error reported for training-run: ' . $trainingRun->getId() .
                    ', error: ' . $trainingRun->getRawOutput()
                );
                break;
            default:
                throw new \Exception('unknown status reported: '.$message['status']);
        }
        $trainingRun->setStatus($message['status']);
        $this->em->flush();
    }

    /**
     * @param TrainingRun $trainingRun
     * @param $imagePath
     */
    private function processNetworkImage(TrainingRun $trainingRun, $imagePath)
    {
        $trainingRun->getNetwork()->setImagePath($imagePath);
        $publicPath = $trainingRun->getNetwork()->getId() . DIRECTORY_SEPARATOR .
            time() . '-' . $trainingRun->getId() . '.jpeg';
        $image = new Imagick();
        $image->readImageBlob($this->networkFilesystem->read($imagePath));
        $image->setImageFormat('PNG8');
        $image->setCompressionQuality(80);
        $this->publicImagesFilesystem->write($publicPath, $image->getImageBlob());
        $trainingRun->setImagePath($publicPath);
        $this->logInfo('generated image: ' . $publicPath . ' for training run: ' . $trainingRun->getId());
    }
}
