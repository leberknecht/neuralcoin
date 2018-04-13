<?php

namespace NetworkBundle\Command;

use DataModelBundle\Entity\Network;
use DataModelBundle\Repository\NetworkRepository;
use NetworkBundle\Service\PredictionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePredictionCommand extends ContainerAwareCommand
{
    /** @var  NetworkRepository */
    private $networkRepository;
    /** @var  PredictionService */
    private $predictionService;
    /** @var  LoggerInterface */
    private $logger;

    protected function configure()
    {
        $this->setName('neuralcoin:predictions:create')
            ->addArgument('networkId', InputArgument::OPTIONAL, 'id of the network')
        ;
    }

    private function injectDeps()
    {
        $this->networkRepository = $this->getContainer()->get('nc.repo.network');
        $this->predictionService = $this->getContainer()->get('nc.prediction');
        $this->logger = $this->getContainer()->get('logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->injectDeps();
        $this->logger->info('creating predictions');

        if ($networkId = $input->getArgument('networkId')) {
            /** @var Network $network */
            $network = $this->networkRepository->find($networkId);
            if (empty($network)) {
                throw new \Exception('unknown network: ' . $networkId);
            }
            $networks = [$network];
        } else {
            /** @var Network[] $networks */
            $networks = $this->networkRepository->findPredictableNetworks();
        }

        foreach($networks as $network) {
            $output->writeln('<fg=green>create prediction for network: </fg=green>' . $network->getId());
            $prediction = $this->requestPrediction($output, $network);
            $output->writeln('<fg=green>Prediction created, id: </fg=green>' .  $prediction->getId());
        }
    }

    /**
     * @param OutputInterface $output
     * @param $network
     * @return \DataModelBundle\Entity\Prediction
     */
    protected function requestPrediction(OutputInterface $output, Network $network)
    {
        $prediction = $this->predictionService->requestPrediction($network);
        $output->writeln(sprintf(
            '<fg=green>Prediction requested for network: </fg=green>%s<fg=green> prediction id: </fg=green>%s',
            $network->getName(),
            $prediction->getId()
        ));

        return $prediction;
    }
}
