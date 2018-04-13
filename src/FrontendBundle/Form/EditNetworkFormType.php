<?php

namespace FrontendBundle\Form;

use DataModelBundle\Entity\Network;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditNetworkFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('timeScope', TextType::class)
            ->add('interpolationInterval', TextType::class)
            ->add('separateInputSymbols', CheckboxType::class, [
                'required' => false,
            ])
            ->add('inputSteps', TextType::class)
            ->add('useOrderBooks', CheckboxType::class,[
                    'required' => false,
            ])
            ->add('orderBookSteps', TextType::class)
            ->add('autopilot', CheckboxType::class, [
                'required' => false,
                'label' => 'Auto-Pilot'
            ])
            ->add('optimizer', ChoiceType::class, [
                'choices' => [
                    'Adaptive moment estimation' => Network::OPTIMIZER_ADAM,
                    'Stochastic Gradient Descent' => Network::OPTIMIZER_SGD,
                    'RMS-Prop' => Network::OPTIMIZER_RMS_PROP,
                    'Momentum' => Network::OPTIMIZER_MOMENTUM,
                ],
            ])
            ->add('activationFunction', ChoiceType::class, [
                'choices' => [
                    'None' => Network::ACTIVATION_FUNCTION_NONE,
                    'Linear' => Network::ACTIVATION_FUNCTION_LINEAR,
                    'TANH' => Network::ACTIVATION_FUNCTION_TANH,
                    'Sigmoid' => Network::ACTIVATION_FUNCTION_SIGMOID,
                ],
            ])
            ->add('hiddenLayers', NumberType::class)
            ->add('customShape', CheckboxType::class, ['required' => false])
            ->add('shape', TextType::class, ['required' => false])
            ->add('learningRate', TextType::class)
            ->add('valueType', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Absolute' => Network::VALUE_TYPE_ABSOLUTE,
                    '% change' => Network::VALUE_TYPE_PERCENTAGE,
                    '% change accumulated' => Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED,
                ],
            ])
            ->add('generateImage', CheckboxType::class, [
                'required' => false
            ])
            ->add('epochsPerTrainingRun', TextType::class)
            ->add('shuffleTrainingData', CheckboxType::class, ['required' => false])
            ->add('balanceHitsAndFails', CheckboxType::class, ['required' => false])
            ->add('useDropout', CheckboxType::class, [
                'required' => false
            ])
            ->add('dropout', NumberType::class, [
                'required' => false
            ])
            ->add('bias', CheckboxType::class, [
                'required' => false
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
            'data_class' => 'DataModelBundle\Entity\Network'
        ]);
    }
}
