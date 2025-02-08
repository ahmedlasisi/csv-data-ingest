<?php

namespace App\Form;

use App\Entity\Broker;
use App\Form\Type\JsonType;
use App\Entity\BrokerConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BrokerConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('broker', BrokerType::class)
            ->add('fileName', TextType::class, [
                'label' => 'File Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter the file name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Enter File Name for Broker',
                    ]),
                ]
            ])
            ->add('fileMapping', JsonType::class, [
                'label' => 'File Mapping (JSON Format)',
                'required' => true,
                'attr' => [
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
                'help' => 'Please provide the file mapping in JSON format. Example:
                    {
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BrokerConfig::class,
        ]);
    }
}
