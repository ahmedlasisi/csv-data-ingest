<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints\Json as JsonConstraint;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Handles transforming json to array and backward
 */
class JsonTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value): mixed
    {
        if (empty($value)) {
            return [];
        }

        $violations = $this->validator->validate($value, new JsonConstraint());
        if (count($violations) > 0) {
            throw new TransformationFailedException('Invalid JSON format.');
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TransformationFailedException('Invalid JSON format.');
        }

        return $decoded;
    }

    /**
     * @inheritDoc
     */
    public function transform($value): mixed
    {
        if (empty($value)) {
            return json_encode([]);
        }

        return json_encode($value, JSON_PRETTY_PRINT);
    }
}
