<?php

namespace FrontendBundle\Form;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\Symbol;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints as Assert;


class CreateNetworkFormType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    private function getKnownExchanges()
    {
        $tradeRepo = $this->em->getRepository('DataModelBundle:Trade');

        return array_combine(
            $tradeRepo->getKnownExchanges(), $tradeRepo->getKnownExchanges()
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['em'];
        $builder
            ->add('name')
            ->add('symbols', EntityType::class, [
                'class' => Symbol::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.averageStepsPerMinute', 'DESC');
                },
                'attr' => [
                    'size' => 12,
                    ''
                ],
                /** @var Symbol $val */
                'choice_attr' => function($val, $key, $index) {
                    // adds a class like attending_yes, attending_no, etc
                    return ['data-symbol-name' => $val->getName()];
                },
                'multiple' => true,
                'required' => false
            ])
            ->add('useMostActiveSymbols', CheckboxType::class, [
                'required' => false
            ])
            ->add('mostActiveSymbolsCount', NumberType::class, [
                'required' => false,
                'attr' => [
                    'disabled' => 'disabled',
                    'value' => 4
                ],
            ])
            ->add('separateInputSymbols', CheckboxType::class, [
                'data' => false,
                'required' => false
            ])
            ->add('useOrderBooks', CheckboxType::class, [
                'required' => false,
                'data' => false
            ])
            ->add('orderBookSteps', RangeType::class, [
                'attr' => [
                    'max' => 60,
                    'min' => 1,
                    'disabled' => 'disabled',
                    'class' => 'range-input'
                ],
                'data' => 5,
            ])
            ->add('timeScope', TextType::class, [
                'data' => Network::DEFAULT_TIME_SCOPE
            ])
            ->add('exchange', ChoiceType::class, [
                'choices' => $this->getKnownExchanges(),
                'placeholder' => 'all',
                'required' => false
            ])
            ->add('autopilot', CheckboxType::class, [
                'required' => false,
                'data' => false,
                'label' => 'Auto-Pilot',
                'attr' => [
                    'data-toggle' => 'tooltip',
                    'title' => 'train + predict periodically'
                ]
            ])
            ->add('activationFunction', ChoiceType::class, [
                'choices' => [
                    'Linear' => Network::ACTIVATION_FUNCTION_LINEAR,
                    'None' => Network::ACTIVATION_FUNCTION_NONE,
                    'TANH' => Network::ACTIVATION_FUNCTION_TANH,
                    'Sigmoid' => Network::ACTIVATION_FUNCTION_SIGMOID,
                ],
                'data' => Network::ACTIVATION_FUNCTION_TANH
            ])
            ->add('optimizer', ChoiceType::class, [
                'choices' => [
                    'Adaptive moment estimation' => Network::OPTIMIZER_ADAM,
                    'Stochastic Gradient Descent' => Network::OPTIMIZER_SGD,
                    'RMS-Prop' => Network::OPTIMIZER_RMS_PROP,
                    'Momentum' => Network::OPTIMIZER_MOMENTUM,
                ],
                'data' => Network::OPTIMIZER_ADAM
            ])
            ->add('hiddenLayers', RangeType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 8,
                    'class' => 'range-input'
                ],
                'data' => 3
            ])
            ->add('customShape', CheckboxType::class, [
                'required' => false,
                'data' => false,
            ])
            ->add('shape', TextType::class, [
                'required' => false,
                'data' => '[]',
                'attr' => [
                    'disabled' => 'disabled'
                ]
            ])
            ->add('learningRate', TextType::class, [
                'data' => 0.001
            ])
            ->add('useDropout', CheckboxType::class, [
                'data' => false,
                'label' => 'use dropout',
                'required' => false,
            ])
            ->add('dropout', NumberType::class, [
                'data' => 0.5,
                'attr' => [
                    'disabled' => 'disabled',
                ]
            ])
            ->add('generateImage', CheckboxType::class, [
                'required' => false,
                'data' => false
            ])
            ->add('bias', CheckboxType::class, [
                'data' => false,
                'label' => 'use bias',
                'required' => false,
            ])
            ->add('epochsPerTrainingRun', TextType::class, [
                'data' => 2,
                'constraints' => [
                    new Assert\Range([
                        'min' => 1,
                        'max' => 10,
                    ])
                ],
                'label' => 'Training epochs'
            ])
            ->add('shuffleTrainingData', CheckboxType::class, [
                'required' => false,
                'data' => false,
            ])
            ->add('balanceHitsAndFails', CheckboxType::class, [
                'required' => false,
                'data' => true,
                'label' => 'Balance hits and misses in training data'
            ])
            ->add('valueType', ChoiceType::class, [
                'choices' => [
                    'Absolute' => Network::VALUE_TYPE_ABSOLUTE,
                    '% change' => Network::VALUE_TYPE_PERCENTAGE,
                    '% change accumulated' => Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED,
                ],
                'data' => Network::VALUE_TYPE_ABSOLUTE,
                'label' => 'Values',
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('interpolateInputs', CheckboxType::class, [
                'data' => false,
                'required' => false,
                'attr' => [
                    'data-toggle' => 'tooltip',
                    'title' => 'If interpolation is disabled, ensure that you use pairs as inputs that have more or less the same amount of trades'
                ]

            ])
            ->add('interpolationInterval', ChoiceType::class, [
                'choices' => [
                    '5 seconds' => 5,
                    '10 seconds' => 10,
                    '15 seconds' => 15,
                    '30 seconds' => 30,
                    '1 minutes' => 60,
                    '2 minutes' => 120,
                    '5 minutes' => 300,
                    '10 minutes' => 600,
                    '15 minutes' => 900,
                ],
                'attr' => [
                    'data-toggle' => 'tooltip',
                    'title' => 'smaller interval = more training sets, higher accuracy'
                ],
                'placeholder' => false,
                'data' => 15,
                'required' => false
            ])
            ->add('inputSteps', RangeType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 60,
                    'class' => 'range-input'
                ],
                'data' => 1
            ])
            ->add('outputConfig', OutputConfigFormType::class)
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-primary',
                    'value' => 'Submit'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DataModelBundle\Entity\Network',
            'em' => null,
        ]);
    }
}
