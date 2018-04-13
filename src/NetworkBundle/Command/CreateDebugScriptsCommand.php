<?php

namespace NetworkBundle\Command;


use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

class CreateDebugScriptsCommand extends ContainerAwareCommand
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var NetworkRepository
     */
    private $networkRepo;

    protected function configure()
    {
        $this->setName('neuralcoin:create-debug-scripts')
            ->addArgument('network-id', InputArgument::REQUIRED)
            ->addArgument('traingin-run-id', InputArgument::OPTIONAL, 'id of the training run')
            ;
    }

    private function setDependencies()
    {
        $this->filesystem = $this->getContainer()->get('oneup_flysystem.networks_filesystem');
        $this->networkRepo = $this->getContainer()->get('nc.repo.network');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setDependencies();
        $networkId = $input->getArgument('network-id');
        /** @var Network $network */
        $network = $this->networkRepo->find($networkId);

        if (!($trainingRunId = $input->getArgument('traingin-run-id'))) {
            $trainingFile = $network->getLastTrainingRun()->getTraningDataFile();
            $output->writeln('<fg=green>No training run specified, using data of last one: </fg=green>' . $trainingFile);
        } else {
            $trainingFile = $networkId . '/training-run-' . $trainingRunId  . '.csv';
        }

        $prediction = $network->getLastPrediction();

        $zip = new ZipArchive();
        $output->writeln('<fg=green>Downloading network- and training files...</fg=green>');
        $zip->open('network-debug-' . $networkId . '.zip', ZipArchive::CREATE);

        $zip->addFromString('training-data.csv', $this->filesystem->get($trainingFile)->read());
        $zip->addFromString('network.tflearn.data-00000-of-00001', $this->filesystem->get(
            $networkId . '/' . $networkId . '.tflearn.data-00000-of-00001'
        )->read());
        $zip->addFromString('network.tflearn.index', $this->filesystem->get(
            $networkId . '/' . $networkId . '.tflearn.index'
        )->read());
        $zip->addFromString('network.tflearn.meta', $this->filesystem->get(
            $networkId . '/' . $networkId . '.tflearn.meta'
        )->read());
        $zip->addFile('python/NetworkManagerTf.py');
        $zip->addFromString('last-prediction-input.csv' , implode(',', $prediction->getInputData()));
        $zip->close();

        $output->writeln('<fg=green>File created: </fg=green>network-debug-' . $networkId . '.zip');

    }
}
