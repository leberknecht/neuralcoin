<?php

namespace Tests\Functional;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Symbol;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CreateNetworkTest extends WebTestCase
{
    /** @var  EntityManager */
    private $em;
    /** @var  Client */
    private $client;

    public function setUp()
    {
        $this->client = $this->createClient(['environment' => 'test']);
        $this->client->disableReboot();
        $this->em = $this->client->getContainer()->get('doctrine.orm.entity_manager');
        $this->em->beginTransaction();
    }

    public function tearDown()
    {
        $this->em->rollback();
    }

    public function testCreateNetworkSuccessInterpolate()
    {
        $crawler = $this->client->request('GET', '/network/create');
        $form = $crawler->selectButton('Submit')->form();
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $this->attachToFormElement(
            $form,
            $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId())['create_network_form'],
            'create_network_form'
        );

        $crawler = $this->client->submit($form);
        $this->client->getResponse()->getContent();
        $this->assertEquals(
            1,
            $crawler->filter('html:contains("Network created!")')->count()
        );

        $crawler = $this->client->request('GET', '/network/list');
        $this->assertEquals(
            1,
            $crawler->filter('html:contains("sweet-test")')->count()
        );

        $network = $this->em->getRepository(Network::class)->findOneBy(['name' => 'sweet-test']);
        $this->client->request('GET', '/network/'.$network->getId().'/train');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateNetworkSuccessNoInterpolate()
    {
        $crawler = $this->client->request('GET', '/network/create');
        $form = $crawler->selectButton('Submit')->form();
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $this->attachToFormElement(
            $form,
                $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId())['create_network_form'],
                'create_network_form'
        );
        unset($form['create_network_form[interpolateInputs]']);

        $crawler = $this->client->submit($form);
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Network created!")')->count()
        );
    }

    public function testCreateNetworkRaisedByPercentage()
    {
        $crawler = $this->client->request('GET', '/network/create');
        $form = $crawler->selectButton('Submit')->form();
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $formData = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $formData['create_network_form']['outputConfig']['type'] = OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY;
        $formData['create_network_form']['outputConfig']['thresholdPercentage'] = null;
        $this->attachToFormElement(
            $form,
            $formData['create_network_form'],
            'create_network_form'
        );

        $crawler = $this->client->submit($form);
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Threshold must be set for this output type")')->count()
        );

        $formData['create_network_form']['outputConfig']['thresholdPercentage'] = 0.25;
        $form = $crawler->selectButton('Submit')->form();
        $this->attachToFormElement(
            $form,
            $formData['create_network_form'],
            'create_network_form'
        );
        $crawler = $this->client->submit($form);
        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Network created!")')->count()
        );
    }

    public function testPreviewNetworkSuccess()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $this->client->request('POST', '/network/preview', $form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['sourceInputs']);
    }

    public function testPreviewNetworkShuffle()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $form['create_network_form']['shuffleTrainingData'] = '1';
        $form['create_network_form']['customShap'] = '1';
        $form['create_network_form']['shape'] = '[6]';
        $form['create_network_form']['balanceHitsAndFails'] = '1';
        $form['create_network_form']['outputConfig']['type'] = OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY;
        $form['create_network_form']['outputConfig']['thresholdPercentage'] =  0.5;
        $this->client->request('POST', '/network/preview', $form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['sourceInputs']);
    }

    public function testPreviewNetworkDropByType()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $form['create_network_form']['outputConfig']['type'] = OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY;
        $form['create_network_form']['outputConfig']['thresholdPercentage'] =  0.5;
        $this->client->request('POST', '/network/preview', $form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['sourceInputs']);
    }

    public function testNoBalanceHits()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $form['create_network_form']['outputConfig']['type'] = OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY;
        $form['create_network_form']['outputConfig']['thresholdPercentage'] =  1000;
        $form['create_network_form']['balanceHitsAndFails'] = true;
        $this->client->request('POST', '/network/preview', $form);
        $response = $this->client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertContains('in- or outputs empty', $response->getContent());
    }

    public function testPreviewOrderBooks()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $form['create_network_form']['useOrderBooks'] = true;
        $form['create_network_form']['orderBookSteps'] = 3;
        $this->client->request('POST', '/network/preview', $form);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPreviewNetworkRaiseBySuccess()
    {
        $allSymbols = $this->em->getRepository(Symbol::class)->findAll();
        $form = $this->getValidFormData([$allSymbols[0]->getId()], $allSymbols[0]->getId());
        $form['create_network_form']['outputConfig']['type'] = OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY;
        $form['create_network_form']['outputConfig']['thresholdPercentage'] = 0.25;
        $this->client->request('POST', '/network/preview', $form);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['sourceInputs']);
    }

    private function attachToFormElement($formElement, $formData, $parentKey = '')
    {
        foreach($formData as $fieldKey => $fieldValue) {
            if (!is_numeric($fieldKey)) {
                $key = $parentKey . '['.$fieldKey.']';
            } else {
                $key = $parentKey;
            }

            if (is_array($fieldValue) && !is_numeric($fieldKey)) {
                $this->attachToFormElement($formElement, $fieldValue, $key);
            }else {
                $formElement[$key] = $fieldValue;
            }
        }
    }

    private function getValidFormData($symbols, $targetSymbolId)
    {
        return [
            'create_network_form' => [
                'symbols' => $symbols,
                'timeScope' => '4 hours',
                'name' => 'sweet-test',
                'interpolateInputs' => '1',
                'learningRate' => '0.001',
                'shape' => '[]',
                'bias' => false,
                'useDropout' => false,
                'dropout' => 0,
                'valueType' => Network::VALUE_TYPE_ABSOLUTE,
                'optimizer' => 'adam',
                'interpolationInterval' => '30',
                'activationFunction' => 'tanh',
                'hiddenLayers' => '1',
                'epochsPerTrainingRun' => '5',
                'inputSteps' => '1',
                'outputConfig' => [
                    'type' => OutputConfig::OUTPUT_TYPE_PREDICT_ONE_PRICE,
                    'stepsAhead' => '10',
                    'pricePredictionSymbol' => $targetSymbolId,
                ],
                'submit' => 'Submit',
            ],
        ];
    }
}
