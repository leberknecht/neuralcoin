<?php

namespace DataModelBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @package DataModelBundle\Entity
 * @ORM\Entity(repositoryClass="DataModelBundle\Repository\TrainingRunRepository")
 * @ORM\Table(name="training_run", indexes={
 *      @ORM\Index(name="created_at_idx", columns={"created_at"}),
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class TrainingRun
{
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PREPARE_TRAINING_DATA = 'preparing training data';
    const STATUS_PREPARED = 'prepared';
    const STATUS_RUNNING = 'in progress';
    const STATUS_FINISHED = 'finished';
    const STATUS_ERROR = 'error';
    const STATUS_SKIPPED = 'skipped';

    /**
     * @var integer
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var Network
     * @ORM\ManyToOne(targetEntity="DataModelBundle\Entity\Network", inversedBy="trainingRuns")
     */
    private $network;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $traningDataFile;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="updated_at")
     */
    private $updatedAt;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $status = self::STATUS_SCHEDULED;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="started_at", nullable=true)
     */
    private $startedAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="finished_at", nullable=true)
     */
    private $finishedAt;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", name="training_data_prepared_at", nullable=true)
     */
    private $trainingDataPreparedAt = null;

    /**
     * @var float
     * @ORM\Column(type="float", nullable=true)
     */
    private $error;

    /**
     * @var int
     * @ORM\Column(type="integer", name="training_set_length", nullable=true)
     */
    private $trainingSetLength = 0;

    /**
     * @var string
     * @ORM\Column(type="string", name="image_path", nullable=true)
     */
    private $imagePath;

    /**
     * @var string
     * @ORM\Column(type="text", name="raw_output", nullable=true)
     */
    private $rawOutput;

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->setCreatedAt(new DateTime());
        $this->setUpdatedAt(new DateTime());
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return TrainingRun
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Network
     */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * @param Network $network
     */
    public function setNetwork(Network $network)
    {
        $this->network = $network;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getTraningDataFile()
    {
        return $this->traningDataFile;
    }

    /**
     * @param string $traningDataFile
     */
    public function setTraningDataFile(string $traningDataFile)
    {
        $this->traningDataFile = $traningDataFile;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param DateTime $startedAt
     */
    public function setStartedAt(DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param DateTime $finishedAt
     */
    public function setFinishedAt(DateTime $finishedAt)
    {
        $this->finishedAt = $finishedAt;
    }

    /**
     * @return float
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param float $error
     */
    public function setError(float $error)
    {
        $this->error = $error;
    }

    /**
     * @return int
     */
    public function getTrainingSetLength()
    {
        return $this->trainingSetLength;
    }

    /**
     * @param int $trainingSetLength
     */
    public function setTrainingSetLength(int $trainingSetLength)
    {
        $this->trainingSetLength = $trainingSetLength;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param string $imagePath
     */
    public function setImagePath(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function getDuration()
    {
        if ($this->finishedAt) {
             return $this->finishedAt->getTimestamp() - $this->createdAt->getTimestamp();
        }
        return null;
    }

    /**
     * @return DateTime
     */
    public function getTrainingDataPreparedAt()
    {
        return $this->trainingDataPreparedAt;
    }

    /**
     * @param DateTime $trainingDataPreparedAt
     */
    public function setTrainingDataPreparedAt(DateTime $trainingDataPreparedAt)
    {
        $this->trainingDataPreparedAt = $trainingDataPreparedAt;
    }

    /**
     * @return string
     */
    public function getRawOutput()
    {
        return $this->rawOutput;
    }

    /**
     * @param string $rawOutput
     */
    public function setRawOutput(string $rawOutput)
    {
        $this->rawOutput = $rawOutput;
    }
}
