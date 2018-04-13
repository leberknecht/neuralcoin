<?php

namespace FrontendBundle\Controller;

use DataModelBundle\Repository\PredictionRepository;
use DataModelBundle\Repository\TrainingRunRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MainController extends Controller
{
    public function indexAction()
    {
        $networks = $this->get('nc.repo.network')->findBy(['autopilot' => true]);
        /** @var TrainingRunRepository $trainingRepo */
        $trainingRepo = $this->get('nc.repo.training_run');
        $trainingRuns = $trainingRepo->findLastTrainingRuns();

        /** @var PredictionRepository $prediction */
        $prediction = $this->get('nc.repo.prediction');
        $predictions = $prediction->findLastPredictions();

        return $this->render('@Frontend/Main/index.html.twig', [
            'networks' => $networks,
            'trainingRuns' => $trainingRuns,
            'predictions' => $predictions
        ]);
    }
}
