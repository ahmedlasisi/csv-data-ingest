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
                'rows' => 5
            ],
            'constraints' => [
                new NotBlank(),
                new ValidJsonConfig(),
            ],
        ]);
    }
}
