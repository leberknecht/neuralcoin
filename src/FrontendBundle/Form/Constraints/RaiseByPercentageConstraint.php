<?php


namespace FrontendBundle\Form\Constraints;


use Symfony\Component\Validator\Constraint;

class RaiseByPercentageConstraint extends Constraint
{
    public $message = 'Threshold must be set for this output type';
    public $service = 'nc.validator.raise_by_percentage';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return $this->service;
    }
}
