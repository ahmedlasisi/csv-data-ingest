<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Handles transforming json to array and backward
 */
class JsonTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function reverseTransform($value): mixed
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \UnexpectedValueException('Invalid JSON format.');
        }

        return $decoded;
    }

    /**
     * @ihneritdoc
     */
    public function transform($value): mixed
    {
        if (empty($value)) {
            return json_encode([]);
        }

        return json_encode($value, JSON_PRETTY_PRINT);
    }
}
