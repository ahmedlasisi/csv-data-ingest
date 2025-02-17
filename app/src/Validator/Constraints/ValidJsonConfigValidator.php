<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidJsonConfigValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\Constraints\ValidJsonConfig */

        if (null === $value || '' === $value) {
            return;
        }

        // If the value is already an array, use it directly
        if (is_array($value)) {
            $data = $value;
        } else {
            $data = json_decode($value, true);
        }

        if (json_last_error() !== JSON_ERROR_NONE || !$this->validateConfigMapping($data)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function validateConfigMapping(array $mapping): bool
    {
        $requiredKeys = [
            "PolicyNumber", "InsuredAmount", "StartDate", "EndDate", "AdminFee",
            "BusinessDescription", "BusinessEvent", "ClientType", "ClientRef",
            "Commission", "EffectiveDate", "InsurerPolicyNumber", "IPTAmount",
            "Premium", "PolicyFee", "PolicyType", "Insurer", "RenewalDate",
            "RootPolicyRef", "Product"
        ];

        // Check if all required keys are present in the mapping
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $mapping) || !is_string($mapping[$key]) || empty(trim($mapping[$key]))) {
                return false; // If missing or not a valid string, return false
            }
        }

        return true; // Mapping is valid
    }
}
