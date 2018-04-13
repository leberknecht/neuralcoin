<?php

namespace NetworkBundle\Command;

use DataModelBundle\Entity\Prediction;
use DataModelBundle\Repository\PredictionRepository;
use NetworkBundle\Service\PredictionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPredictionsCommand extends ContainerAwareCommand
{
    /** @var  PredictionService */
    private $predictionService;
    /** @var  PredictionRepository */
    private $predictionRepository;
    /** @var  LoggerInterface */
    private $logger;

    protected function configure()
    {
        $this->setName('neuralcoin:predictions:check')
            ->addArgument('predictionId', InputArgument::OPTIONAL, 'id of prediction to check')
        ;
    }

    private function injectDeps()
    {
        $this->predictionService = $this->getContainer()->get('nc.prediction');
        $this->predictionRepository = $this->getContainer()->get('nc.repo.prediction');
        $this->logger = $this->getContainer()->get('logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->injectDeps();

        $this->logger->info('checking predictions');
        $output->writeln('<fg=green>Checking open predictions</fg=green>');


        if ($predictionId = $input->getArgument('predictionId')) {
            /** @var Prediction[] $openPredictions */
            $openPredictions = $this->predictionRepository->find($predictionId);
        } else {
            /** @var Prediction[] $openPredictions */
            $openPredictions = $this->predictionRepository->findBy(['finished' => false]);
        }

        $output->writeln('<fg=green>Pending: </fg=green>' . count($openPredictions));

        foreach($openPredictions as $prediction) {
            $output->writeln('checking prediction: ' . $prediction->getId());
            $this->predictionService->checkPrediction($prediction);
            if($prediction->isFinished()) {
                $output->writeln(sprintf(
                    'prediction %s finished. Price at prediction: %s, predicted value: %s, actual-price: %s, change %s / %s (predicted, actual) ',
                    $prediction->getId(),
                    $prediction->getPriceAtPrediction(),
                    $prediction->getPredictedValue(),
                    $prediction->getActualPrice(),
                    $prediction->getPredictedChange(),
                    $prediction->getActualChange()
                ));
            }
        }
    }
}
