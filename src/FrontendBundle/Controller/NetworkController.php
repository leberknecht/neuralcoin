<?php

namespace FrontendBundle\Controller;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OrderBook;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\PredictionRepository;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Repository\TrainingRunRepository;
use Doctrine\Common\Collections\ArrayCollection;
use FrontendBundle\Form\CreateNetworkFormType;
use FrontendBundle\Form\EditNetworkFormType;
use Imagick;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NetworkController extends Controller
{
    public function createNetworkAction(Request $request)
    {
        $form = $this->createForm(CreateNetworkFormType::class, null, [ 'em' => $this->get('doctrine.orm.entity_manager')]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Network $network */
            $network = $form->getData();
            $network->getOutputConfig()->setNetwork($network);
            $this->get('doctrine.orm.entity_manager')->persist($network);
            $this->get('doctrine.orm.entity_manager')->flush();
            $this->get('nc.network_creator')->createPybrainNetwork($network);
            $this->addFlash('notice', 'Network created! <a href="'.$this->get('router')->generate('frontend_network_show', ['id' => $network->getId()]).'">show</a>');
        }

        $symbols = $this->get('nc.repo.symbol')->findAll();
        return $this->render(
            '@Frontend/Network/create.html.twig',
            [
                'createForm' => $form->createView(),
                'knownSymbols' => $symbols
            ]
        );
    }

    public function previewNetworkDataAction(Request $request)
    {
        $form = $this->createForm(CreateNetworkFormType::class, null, [ 'em' => $this->get('doctrine.orm.entity_manager')]);
        $form->submit($request->request->get($form->getName()));
        /** @var Network $network */
        $network = $form->getData();
        $networkData = $this->get('nc.network_data')->getNetworkData($network, true);
        $serializer = $this->get('nc.serializer');
        $result = $serializer->serialize($networkData, ['network-create-preview']);
        return new Response($result);
    }

    /**
     * @param Network $network
     * @return Response
     */
    public function showNetworkAction(Network $network)
    {
        /** @var TrainingRunRepository $trainingRunRepo */
        $trainingRunRepo = $this->get('nc.repo.training_run');
        $lastTrainingRuns = $trainingRunRepo->findLastTrainingRuns($network);

        /** @var PredictionRepository $predictionRepo */
        $predictionRepo = $this->get('nc.repo.prediction');
        $predictionChartData = $predictionRepo->findPredictionStats($network);
        return $this->render('@Frontend/Network/show.html.twig', [
            'network' => $network,
            'lastTrainingRuns' => $lastTrainingRuns,
            'predictionChartData' => json_encode($predictionChartData)
        ]);
    }

    public function resetAction(Network $network)
    {
        $entityManager = $this->get('doctrine.orm.default_entity_manager');
        foreach($network->getPredictions() as $prediction) {
            $entityManager->remove($prediction);
        }
        foreach($network->getTrainingRuns() as $trainingRun) {
            $entityManager->remove($trainingRun);
        }
        $entityManager->flush();
        $this->get('nc.network_creator')->createPybrainNetwork($network);
        $this->get('session')->getFlashBag()->add('notice', 'Network resetted');

        return $this->redirectToRoute('frontend_network_show', ['id' => $network->getId()]);
    }

    public function deleteAction(Network $network)
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $em->remove($network);
        $em->flush();
        $session = $this->get('session');
        $session->getFlashBag()->add('notice', 'Network removed!');
        return $this->redirectToRoute('frontend_list_network');
    }

    public function listNetworkAction()
    {
        $networks = $this->get('nc.repo.network')->findBy([], [
            'autopilot' => 'DESC',
        ]);
        return $this->render('@Frontend/Network/list.html.twig', [
            'networks' => $networks
        ]);
    }

    public function trainNetworkAction(Network $network)
    {
        $trainingRun = $this->get('nc.network_training')->scheduleTraining($network);
        $this->get('session')->getFlashBag()->add('notice', 'Training scheduled, run-id: '.$trainingRun->getId());
        return $this->redirectToRoute('frontend_network_training_status', ['id' => $trainingRun->getId()]);
    }

    public function predictJsonAction(Request $request, Network $network)
    {
        $em = $this->get('doctrine.orm.default_entity_manager');
        $prediction = new Prediction();
        $prediction->setNetwork($network);
        $em->persist($prediction);
        $em->flush();

        $symbolOverwrite = null;
        if ($symbolOverwrite = $request->get('symbol')) {
            if (count($network->getSymbols()) > 1 && !$network->isSeparateInputSymbols()) {
                throw new \Exception('requested symbol overwrite for a network with multiple symbols and no separation');
            }
            $symbolOverwrite = $this->get('nc.repo.symbol')->findOneBy(['name' => $symbolOverwrite]);
        }
        $prediction = $this->get('nc.prediction')->getPrediction($prediction, $symbolOverwrite);
        $responseContent = $this->get('nc.serializer')->serialize($prediction, ['dropRaisePrediction']);
        $em->remove($prediction);
        $em->flush($prediction);
        return new Response($responseContent);
    }

    public function predictAction(Network $network)
    {
        $prediction = $this->get('nc.prediction')->requestPrediction($network);
        $this->get('session')->getFlashBag()->add('notice', 'Prediction requested, preparing input-data!');
        return $this->redirectToRoute('frontend_prediction_status', ['id' => $prediction->getId()]);
    }

    public function predictionStatusAction(Prediction $prediction)
    {
        return $this->render('@Frontend/Network/prediction.html.twig',['prediction' => $prediction]);
    }

    public function plotNetworkAction(Network $network)
    {
        $image = new Imagick();
        $content = $this->get('oneup_flysystem.networks_filesystem')->read($network->getImagePath());
        $image->readImageBlob($content);
        $image->setImageFormat("PNG24");
        return new Response($image->getImageBlob(), 200, ['Content-Type' => 'image/png']);
    }

    public function editNetworkAction(Request $request, Network $network)
    {
        $form = $this->createForm(EditNetworkFormType::class, $network);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Network $network */
            $network = $form->getData();
            $this->get('doctrine.orm.entity_manager')->persist($network);
            $this->get('doctrine.orm.entity_manager')->flush();
            $this->addFlash('notice', 'Network updated!');
        }

        return $this->render('@Frontend/Network/edit.html.twig', [
            'editForm' => $form->createView(),
            'network' => $network
        ]);
    }

    public function getSupportedSymbolsAction(Request $request, $exchange)
    {
        $symbolsRepo = $this->get('nc.repo.symbol');

        $symbols = $symbolsRepo->findSupportedSymbols(
            'all' == $exchange ? false :$exchange,
            $request->get('useOrderBooks', false)
        );

        $serializer = $this->get('nc.serializer');
        $result = $serializer->serialize($symbols, ["get-exchange-symbols"]);
        return new Response($result);
    }
}
