<?php

namespace App\Form;

use App\Entity\Broker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BrokerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter Broker name',
                    'class' => 'form-control mb-2',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Enter Broker Name',
                    ]),
                ],
                'label_attr' => [
                    'class' => 'required form-label',
                ]
            ]) ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Broker::class,
        ]);
    }
}
