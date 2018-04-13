<?php

namespace FrontendBundle\Form\Constraints;

use DataModelBundle\Entity\OutputConfig;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RaiseByPercentageValidator extends ConstraintValidator
{
    /**
     * @param OutputConfig $outputConfig
     * @param Constraint $constraint
     */
    public function validate($outputConfig, Constraint $constraint)
    {
        if (in_array($outputConfig->getType(), [
                OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY,
                OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY,
            ]) &&
            empty($outputConfig->getThresholdPercentage())
        ) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}