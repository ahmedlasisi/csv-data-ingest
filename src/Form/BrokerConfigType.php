<?php

namespace App\Form;

use App\Entity\Broker;
use App\Entity\BrokerConfig;
use App\Form\Type\JsonType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BrokerConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
          
            ->add('broker', BrokerType::class)

            ->add('fileName', TextType::class, [
                'label' => 'File Name',
                'required' => true
            ])
            ->add('fileMapping', JsonType::class, [
                'label' => 'File Mapping (JSON Format)',
                'required' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BrokerConfig::class,
        ]);
    }
}
