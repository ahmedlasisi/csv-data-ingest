<?php

namespace App\Form;

use App\Form\Type\JsonType;
use App\Entity\BrokerConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BrokerConfigType extends AbstractType
{
    private const JSON_PLACEHOLDER = '{
        "PolicyNumber": "PolicyNumber",
        "InsuredAmount": "InsuredAmount",
        "StartDate": "StartDate",
        "EndDate": "EndDate",
        "AdminFee": "AdminFee",
        "BusinessDescription": "BusinessDescription",
        "BusinessEvent": "BusinessEvent",
        "ClientType": "ClientType",
        "ClientRef": "ClientRef",
        "Commission": "Commission",
        "EffectiveDate": "EffectiveDate",
        "InsurerPolicyNumber": "InsurerPolicyNumber",
        "IPTAmount": "IPTAmount",
        "Premium": "Premium",
        "PolicyFee": "PolicyFee",
        "PolicyType": "PolicyType",
        "Insurer": "Insurer",
        "RenewalDate": "RenewalDate",
        "RootPolicyRef": "RootPolicyRef",
        "Product": "Product"
    }';

    private const JSON_HELP_TEXT = 'Please provide the file mapping in JSON format. Example:
                    {
        "PolicyNumber": "PolicyNumber",
        "InsuredAmount": "InsuredAmount",
        "StartDate": "StartDate",
        "EndDate": "EndDate",
        "AdminFee": "AdminFee",
        "BusinessDescription": "BusinessDescription",
        "BusinessEvent": "BusinessEvent",
        "ClientType": "ClientType",
        "ClientRef": "ClientRef",
        "Commission": "Commission",
        "EffectiveDate": "EffectiveDate",
        "InsurerPolicyNumber": "InsurerPolicyNumber",
        "IPTAmount": "IPTAmount",
        "Premium": "Premium",
        "PolicyFee": "PolicyFee",
        "PolicyType": "PolicyType",
        "Insurer": "Insurer",
        "RenewalDate": "RenewalDate",
        "RootPolicyRef": "RootPolicyRef",
        "Product": "Product"
    }';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('broker', BrokerType::class)
            ->add('fileName', TextType::class, [
                'label' => 'CSV File Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter the file name'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Enter CSV File Name for Broker',
                    ]),
                ]
            ])
            ->add('fileMapping', JsonType::class, [
                'label' => 'File Mapping (JSON Format) modify the value of each keys to match the column names in the CSV file',
                'required' => true,
                'attr' => [
                    'placeholder' => self::JSON_PLACEHOLDER
                ],
                'help' => self::JSON_HELP_TEXT
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BrokerConfig::class,
        ]);
    }
}
