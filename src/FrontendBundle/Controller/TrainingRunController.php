<?php


namespace FrontendBundle\Controller;


use DataModelBundle\Entity\TrainingRun;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class TrainingRunController extends Controller
{
    /**
     * @param TrainingRun $trainingRun
     * @return Response
     */
    public function showTrainingStatusAction(TrainingRun $trainingRun)
    {
        return $this->render('@Frontend/TrainingRun/show.html.twig', [
            'trainingRun' => $trainingRun
        ]);
    }

    /**
     * @param TrainingRun $trainingRun
     * @return Response
     */
    public function getTrainingDataFileAction(TrainingRun $trainingRun)
    {
        $content = $this->get('oneup_flysystem.networks_filesystem')->read($trainingRun->getTraningDataFile());
        $response = new Response($content);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'training-data-' . $trainingRun->getId() . '.csv'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}