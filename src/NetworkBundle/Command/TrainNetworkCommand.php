<?php

namespace NetworkBundle\Command;

use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use NetworkBundle\Service\NetworkTrainingService;
use NetworkBundle\Service\PredictionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TrainNetworkCommand extends ContainerAwareCommand
{
    /** @var  NetworkRepository */
    private $networkRepository;
    /** @var  NetworkTrainingService */
    private $trainingService;
    /** @var  LoggerInterface */
    private $logger;

    protected function configure()
    {
        $this->setName('neuralcoin:train-network')
            ->addArgument('networkId', InputArgument::OPTIONAL, 'id of the network')
        ;
    }
    private function injectDeps()
    {
        $this->networkRepository = $this->getContainer()->get('nc.repo.network');
        $this->trainingService = $this->getContainer()->get('nc.network_training');
        $this->logger = $this->getContainer()->get('logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->injectDeps();
        $this->logger->info('train networks');

        if($networkId = $input->getArgument('networkId')) {
            $network = $this->networkRepository->find($networkId);
            if (empty($network)) {
                throw new \Exception('unknown network: ' . $networkId);
            }
            /** @var Network[] $networks */
            $networks = [$network];

        } else {
            /** @var Network[] $networks */
            $networks = $this->networkRepository->findBy(['autopilot' => true], ['name' => 'ASC']);
        }

        foreach($networks as $network) {
            $this->trainNetwork($output, $network);
        }
    }

    /**
     * @param OutputInterface $output
     * @param Network $network
     */
    protected function trainNetwork(OutputInterface $output, Network $network)
    {
        $output->writeln('<fg=green>train network: </fg=green>' . $network->getId());
        $trainingRun = $this->trainingService->scheduleTraining($network);
        $output->writeln(sprintf(
            '<fg=green>Scheduled training for network: </fg=green>%s<fg=green> training run id: %s</fg=green>',
            $network->getName(),
            $trainingRun->getId()
        ));
    }
}
