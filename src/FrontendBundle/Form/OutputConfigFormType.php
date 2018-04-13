<?php

namespace FrontendBundle\Form;

use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Symbol;
use Doctrine\ORM\EntityRepository;
use FrontendBundle\Form\Constraints\RaiseByPercentageConstraint;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class OutputConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => OutputConfig::$formChoiceMapping
            ])
            ->add('stepsAhead', NumberType::class, [
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 5000

                    ])
                ],
            ])
            ->add(
                'pricePredictionSymbol',
                EntityType::class,
                [
                    'class' => Symbol::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.averageStepsPerMinute', 'DESC');
                    },
                ]
            )
            ->add('thresholdPercentage', TextType::class, [
                'required' => false,
                'label' => 'Threshold in %',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DataModelBundle\Entity\OutputConfig',
            'constraints' => [
                new RaiseByPercentageConstraint()
            ]
        ]);
    }
}
