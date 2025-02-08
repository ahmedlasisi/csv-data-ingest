<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidJsonConfig extends Constraint
{
    public $message = 'The JSON configuration is invalid.';

    public function validatedBy(): string
    {
        return ValidJsonConfigValidator::class;
    }
}
