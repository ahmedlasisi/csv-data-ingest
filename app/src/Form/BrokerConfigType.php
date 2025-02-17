<?php

namespace App\Form;

use App\Form\Type\JsonType;
use App\Util\JsonPlaceholders;
use App\Entity\BrokerConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
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
                    'placeholder' => JsonPlaceholders::BROKER_CONFIG
                ],
                'help' => JsonPlaceholders::BROKER_CONFIG
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BrokerConfig::class,
        ]);
    }
}
