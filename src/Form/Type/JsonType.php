<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\DataTransformer\JsonTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Validator\Constraints\ValidJsonConfig;

class JsonType extends AbstractType
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new JsonTransformer($this->validator));
    }

    public function getParent()
    {
        return TextareaType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'rows' => 5,
                'placeholder' => '{
                    "PolicyNumber": "12345",
                    "InsuredAmount": "100000",
                    "StartDate": "2025-01-01",
                    "EndDate": "2025-12-31",
                    "AdminFee": "100",
                    "BusinessDescription": "Description",
                    "BusinessEvent": "Event",
                    "ClientType": "Type",
                    "ClientRef": "Ref123",
                    "Commission": "10%",
                    "EffectiveDate": "2025-01-01",
                    "InsurerPolicyNumber": "54321",
                    "IPTAmount": "20",
                    "Premium": "200",
                    "PolicyFee": "50",
                    "PolicyType": "TypeA",
                    "Insurer": "InsurerName",
                    "RenewalDate": "2026-01-01",
                    "RootPolicyRef": "RootRef",
                    "Product": "ProductName"
                }'
            ],
            'constraints' => [
                new NotBlank(),
                new ValidJsonConfig(),
            ],
        ]);
    }
}
